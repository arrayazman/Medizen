<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SimrsSatuSehatService
{
    protected SatuSehatService $satusehat;

    public function __construct(SatuSehatService $satusehat)
    {
        $this->satusehat = $satusehat;
    }

    /**
     * Get list of radiology requests from SIMRS SIK
     * Logic from proses_servicerequest_radiologi.php (Aksi: tampil)
     */
    public function getPendingRequests(string $startDate, string $endDate, ?string $keyword = null)
    {
        $parameters = [$startDate, $endDate];
        
        $whereSql = "";
        if ($keyword) {
            $whereSql = " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ? OR pasien.no_ktp LIKE ? OR pegawai.nama LIKE ? OR jns_perawatan_radiologi.nm_perawatan LIKE ? OR permintaan_radiologi.noorder LIKE ?)";
            $keywords = array_fill(0, 7, "%{$keyword}%");
            $parameters = array_merge($parameters, $keywords);
        }

        // We use LEFT JOIN for SS-specific and metadata tables to ensure visibility
        $sql = "SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, 
                       IFNULL(pasien.no_ktp, '') as no_ktp_pasien,
                       reg_periksa.kd_dokter, IFNULL(pegawai.nama, '-') as nama_dokter, 
                       IFNULL(pegawai.no_ktp, '') as no_ktp_dokter,
                       IFNULL(satu_sehat_encounter.id_encounter, '') as id_encounter, 
                       permintaan_radiologi.noorder, permintaan_radiologi.tgl_permintaan, 
                       permintaan_radiologi.jam_permintaan, permintaan_radiologi.diagnosa_klinis,
                       jns_perawatan_radiologi.nm_perawatan, IFNULL(satu_sehat_mapping_radiologi.code, '') as code,
                       IFNULL(satu_sehat_mapping_radiologi.system, '') as system, 
                       IFNULL(satu_sehat_mapping_radiologi.display, '') as display,
                       permintaan_pemeriksaan_radiologi.kd_jenis_prw,
                       CONCAT(permintaan_radiologi.tgl_permintaan, ' ', permintaan_radiologi.jam_permintaan) as tgl_jam,
                       IFNULL(satu_sehat_servicerequest_radiologi.id_servicerequest, '') as id_servicerequest
                FROM reg_periksa 
                INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis 
                LEFT JOIN pegawai ON pegawai.nik = reg_periksa.kd_dokter 
                LEFT JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat 
                INNER JOIN permintaan_radiologi ON permintaan_radiologi.no_rawat = reg_periksa.no_rawat 
                INNER JOIN permintaan_pemeriksaan_radiologi ON permintaan_pemeriksaan_radiologi.noorder = permintaan_radiologi.noorder 
                INNER JOIN jns_perawatan_radiologi ON jns_perawatan_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw 
                LEFT JOIN satu_sehat_mapping_radiologi ON satu_sehat_mapping_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw 
                LEFT JOIN satu_sehat_servicerequest_radiologi ON satu_sehat_servicerequest_radiologi.noorder = permintaan_pemeriksaan_radiologi.noorder 
                AND satu_sehat_servicerequest_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
                WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ? 
                {$whereSql}
                ORDER BY permintaan_radiologi.noorder DESC";

        return DB::connection('simrs')->select($sql, $parameters);
    }

    /**
     * Send a single ServiceRequest from SIMRS to Satu Sehat
     * Logic from proses_servicerequest_radiologi.php (Aksi: post/put)
     */
    public function sendServiceRequest(array $data)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) {
            $logs[] = ['type' => $type, 'msg' => $msg];
        };

        try {
            // 1. Get Patient ID
            $addLog('info', 'Mendapatkan Patient ID via NIK: ' . $data['no_ktp_pasien']);
            $patientId = $this->getPatientId($data['no_ktp_pasien']);
            if (!$patientId) throw new \Exception('Patient ID tidak ditemukan di SatuSehat. Cek NIK pasien.');
            $addLog('ok', 'Patient ID: ' . $patientId);

            // 2. Get Practitioner ID
            $addLog('info', 'Mendapatkan Practitioner ID via NIK dokter: ' . $data['no_ktp_dokter']);
            $practitionerId = $this->getPractitionerId($data['no_ktp_dokter']);
            if (!$practitionerId) throw new \Exception('Practitioner ID tidak ditemukan di SatuSehat. Cek NIK dokter.');
            $addLog('ok', 'Practitioner ID: ' . $practitionerId);

            // 3. Build FHIR Resource
            $orgId = $this->satusehat->getOrganizationId();
            $tglJam = $data['tgl_permintaan'] . ' ' . $data['jam_permintaan'];
            $authoredOn = \Carbon\Carbon::parse($tglJam)->toIso8601String();

            $displayEncounter = "Permintaan {$data['nm_perawatan']} atas nama pasien {$data['nm_pasien']} No.RM {$data['no_rkm_medis']} No.Rawat {$data['no_rawat']}, pada tanggal {$tglJam}";

            $resource = [
                'resourceType' => 'ServiceRequest',
                'identifier' => [[
                    'system' => "http://sys-ids.kemkes.go.id/acsn/{$orgId}",
                    'value' => $data['noorder']
                ]],
                'status' => 'active',
                'intent' => 'order',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '363679005',
                        'display' => 'Imaging'
                    ]]
                ]],
                'code' => [
                    'coding' => [[
                        'system' => $data['system'],
                        'code' => $data['code'],
                        'display' => $data['display']
                    ]],
                    'text' => $data['nm_perawatan']
                ],
                'subject' => ['reference' => "Patient/{$patientId}"],
                'encounter' => [
                    'reference' => "Encounter/{$data['id_encounter']}",
                    'display' => $displayEncounter
                ],
                'authoredOn' => $authoredOn,
                'requester' => [
                    'reference' => "Practitioner/{$practitionerId}",
                    'display' => $data['nama_dokter']
                ],
                'performer' => [[
                    'reference' => "Organization/{$orgId}",
                    'display' => 'Ruang Radiologi/Petugas Radiologi'
                ]],
                'reasonCode' => [[
                    'text' => $data['diagnosa_klinis']
                ]]
            ];

            $isUpdate = !empty($data['id_servicerequest']);
            $method = $isUpdate ? 'PUT' : 'POST';
            $path = 'ServiceRequest' . ($isUpdate ? '/' . $data['id_servicerequest'] : '');

            if ($isUpdate) {
                $resource['id'] = $data['id_servicerequest'];
            }

            $addLog('info', "{$method} → {$path}");
            $addLog('info', "ACSN: " . $data['noorder']);

            $response = $this->satusehatRequest($method, $path, $resource);

            if (!$response['success']) {
                $errorMsg = $response['data']['issue'][0]['diagnostics'] ?? $response['data']['issue'][0]['details']['text'] ?? $response['body'] ?? 'Unknown error';
                throw new \Exception("FHIR {$method} gagal: " . $errorMsg);
            }

            $fhirId = $response['data']['id'] ?? '';
            $addLog('ok', "Berhasil {$method} ServiceRequest. ID: {$fhirId}");

            // 4. Save to SIMRS DB
            $this->saveToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return [
                'success' => true,
                'id_servicerequest' => $fhirId,
                'logs' => $logs
            ];

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'logs' => $logs
            ];
        }
    }

    protected function getPatientId(string $nik)
    {
        $response = $this->satusehat->findPatientByNik($nik);
        return $response['data']['entry'][0]['resource']['id'] ?? null;
    }

    protected function getPractitionerId(string $nik)
    {
        $token = $this->satusehat->getAccessToken();
        if (!$token) return null;

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->get($this->satusehatGetBaseUrl() . "/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|{$nik}");

        if ($response->successful()) {
            return $response->json()['entry'][0]['resource']['id'] ?? null;
        }
        return null;
    }

    protected function satusehatRequest(string $method, string $path, array $body)
    {
        $token = $this->satusehat->getAccessToken();
        $url = $this->satusehatGetBaseUrl() . '/' . $path;

        $request = Http::withToken($token)
            ->withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/fhir+json'])
            ->timeout(30);

        $response = match (strtoupper($method)) {
            'POST' => $request->post($url, $body),
            'PUT' => $request->put($url, $body),
            default => $request->get($url),
        };

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'body' => $response->body()
        ];
    }

    protected function satusehatGetBaseUrl()
    {
        return rtrim(config('satusehat.base_url'), '/');
    }

    protected function saveToSimrs($noorder, $kd_jenis_prw, $fhirId)
    {
        $exists = DB::connection('simrs')->table('satu_sehat_servicerequest_radiologi')
            ->where('noorder', $noorder)
            ->where('kd_jenis_prw', $kd_jenis_prw)
            ->exists();

        if ($exists) {
            DB::connection('simrs')->table('satu_sehat_servicerequest_radiologi')
                ->where('noorder', $noorder)
                ->where('kd_jenis_prw', $kd_jenis_prw)
                ->update(['id_servicerequest' => $fhirId]);
        } else {
            DB::connection('simrs')->table('satu_sehat_servicerequest_radiologi')
                ->insert([
                    'noorder' => $noorder,
                    'kd_jenis_prw' => $kd_jenis_prw,
                    'id_servicerequest' => $fhirId
                ]);
        }
    }

    /**
     * Get list of imaging studies (RESULTS) from SIMRS SIK
     */
    public function getPendingImagingStudies(string $startDate, string $endDate, ?string $keyword = null)
    {
        $parameters = [$startDate, $endDate];
        
        $whereSql = "";
        if ($keyword) {
            $whereSql = " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ? OR pasien.no_ktp LIKE ? OR permintaan_radiologi.noorder LIKE ?)";
            $keywords = array_fill(0, 5, "%{$keyword}%");
            $parameters = array_merge($parameters, $keywords);
        }

        // Logic: we need orders that HAVE ServiceRequest ID (sent) and HAVE results in SIMRS
        $sql = "SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, 
                       IFNULL(pasien.no_ktp, '') as no_ktp_pasien,
                       pasien.tgl_lahir, pasien.jk,
                       IFNULL(satu_sehat_encounter.id_encounter, '') as id_encounter, 
                       permintaan_radiologi.noorder, permintaan_radiologi.tgl_permintaan, 
                       permintaan_radiologi.jam_permintaan,
                       jns_perawatan_radiologi.nm_perawatan,
                       permintaan_pemeriksaan_radiologi.kd_jenis_prw,
                       IFNULL(satu_sehat_mapping_radiologi.code, '') as code,
                       IFNULL(satu_sehat_mapping_radiologi.system, '') as system, 
                       IFNULL(satu_sehat_mapping_radiologi.display, '') as display,
                       IFNULL(satu_sehat_servicerequest_radiologi.id_servicerequest, '') as id_servicerequest,
                       IFNULL(satu_sehat_imagingstudy_radiologi.id_imaging, '') as id_imaging,
                       hasil_radiologi.hasil as expertise,
                       permintaan_radiologi.tgl_sampel, permintaan_radiologi.jam_sampel,
                       permintaan_radiologi.tgl_hasil, permintaan_radiologi.jam_hasil
                FROM reg_periksa 
                INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis 
                LEFT JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat 
                INNER JOIN permintaan_radiologi ON permintaan_radiologi.no_rawat = reg_periksa.no_rawat 
                INNER JOIN permintaan_pemeriksaan_radiologi ON permintaan_pemeriksaan_radiologi.noorder = permintaan_radiologi.noorder 
                INNER JOIN jns_perawatan_radiologi ON jns_perawatan_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw 
                LEFT JOIN satu_sehat_mapping_radiologi ON satu_sehat_mapping_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw 
                LEFT JOIN satu_sehat_servicerequest_radiologi ON satu_sehat_servicerequest_radiologi.noorder = permintaan_pemeriksaan_radiologi.noorder 
                    AND satu_sehat_servicerequest_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
                INNER JOIN hasil_radiologi ON hasil_radiologi.no_rawat = permintaan_radiologi.no_rawat 
                LEFT JOIN satu_sehat_imagingstudy_radiologi ON satu_sehat_imagingstudy_radiologi.noorder = permintaan_pemeriksaan_radiologi.noorder 
                    AND satu_sehat_imagingstudy_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
                WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ? 
                {$whereSql}
                ORDER BY permintaan_radiologi.noorder DESC";

        return DB::connection('simrs')->select($sql, $parameters);
    }

    /**
     * Send ImagingStudy metadata to SatuSehat (SIMRS context)
     */
    public function sendImagingStudy(array $data)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) {
            $logs[] = ['type' => $type, 'msg' => $msg];
        };

        try {
            if (empty($data['id_servicerequest'])) {
                throw new \Exception('ServiceRequest ID belum ada. Kirim ServiceRequest terlebih dahulu.');
            }

            // 1. Get Patient ID
            $addLog('info', 'Mendapatkan Patient ID via NIK: ' . $data['no_ktp_pasien']);
            $patientId = $this->getPatientId($data['no_ktp_pasien']);
            if (!$patientId) throw new \Exception('Patient ID tidak ditemukan di SatuSehat.');
            
            // 2. Build ImagingStudy Resource
            $orgId = $this->satusehat->getOrganizationId();
            $started = \Carbon\Carbon::parse(($data['tgl_sampel'] != '0000-00-00' ? $data['tgl_sampel'] : $data['tgl_permintaan']) . ' ' . ($data['jam_sampel'] != '00:00:00' ? $data['jam_sampel'] : $data['jam_permintaan']))->toIso8601String();

            // StudyInstanceUID
            $uid = "2.25." . abs(crc32($data['noorder'] . $data['kd_jenis_prw']));

            $resource = [
                'resourceType' => 'ImagingStudy',
                'status' => 'available',
                'subject' => ['reference' => "Patient/{$patientId}"],
                'encounter' => ['reference' => "Encounter/{$data['id_encounter']}"],
                'started' => $started,
                'basedOn' => [['reference' => "ServiceRequest/{$data['id_servicerequest']}"]],
                'referrer' => [
                    'reference' => "Organization/{$orgId}"
                ],
                'interpreter' => [
                    ['reference' => "Organization/{$orgId}"]
                ],
                'numberOfSeries' => 1,
                'numberOfInstances' => 1,
                'procedureCode' => [[
                    'coding' => [[
                        'system' => $data['system'] ?: 'http://loinc.org',
                        'code' => $data['code'],
                        'display' => $data['display']
                    ]]
                ]],
                'location' => ['reference' => "Organization/{$orgId}"],
                'note' => [['text' => strip_tags($data['expertise'] ?? 'Hasil pemeriksaan radiologi')]],
                'identifier' => [
                    [
                        'system' => 'urn:dicom:uid',
                        'value' => "urn:oid:{$uid}"
                    ],
                    [
                        'system' => "http://sys-ids.kemkes.go.id/acsn/{$orgId}",
                        'value' => $data['noorder']
                    ]
                ],
                'series' => [[
                    'uid' => "{$uid}.1",
                    'number' => 1,
                    'modality' => [
                        'system' => 'http://dicom.nema.org/resources/ontology/DCM',
                        'code' => 'RAD'
                    ],
                    'description' => $data['nm_perawatan'],
                    'instance' => [[
                        'uid' => "{$uid}.1.1",
                        'sopClass' => [
                            'system' => 'urn:ietf:rfc:3986',
                            'code' => 'urn:oid:1.2.840.10008.5.1.4.1.1.7'
                        ]
                    ]]
                ]]
            ];

            $isUpdate = !empty($data['id_imaging']);
            $method = $isUpdate ? 'PUT' : 'POST';
            $path = 'ImagingStudy' . ($isUpdate ? '/' . $data['id_imaging'] : '');

            if ($isUpdate) $resource['id'] = $data['id_imaging'];

            $addLog('info', "{$method} → {$path}");
            $response = $this->satusehatRequest($method, $path, $resource);

            if (!$response['success']) {
                $errorMsg = $response['data']['issue'][0]['diagnostics'] ?? $response['body'] ?? 'Unknown error';
                throw new \Exception("FHIR {$method} ImagingStudy gagal: " . $errorMsg);
            }

            $fhirId = $response['data']['id'] ?? '';
            $addLog('ok', "Berhasil {$method} ImagingStudy. ID: {$fhirId}");

            // Save to SIMRS DB
            $this->saveImagingToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return [
                'success' => true,
                'id_imaging' => $fhirId,
                'logs' => $logs
            ];

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'logs' => $logs
            ];
        }
    }

    protected function saveImagingToSimrs($noorder, $kd_jenis_prw, $fhirId)
    {
        $exists = DB::connection('simrs')->table('satu_sehat_imagingstudy_radiologi')
            ->where('noorder', $noorder)
            ->where('kd_jenis_prw', $kd_jenis_prw)
            ->exists();

        if ($exists) {
            DB::connection('simrs')->table('satu_sehat_imagingstudy_radiologi')
                ->where('noorder', $noorder)
                ->where('kd_jenis_prw', $kd_jenis_prw)
                ->update(['id_imaging' => $fhirId]);
        } else {
            DB::connection('simrs')->table('satu_sehat_imagingstudy_radiologi')
                ->insert([
                    'noorder' => $noorder,
                    'kd_jenis_prw' => $kd_jenis_prw,
                    'id_imaging' => $fhirId
                ]);
        }
    }
}
