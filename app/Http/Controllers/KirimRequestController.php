<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KirimRequestController extends Controller
{
    // ─────────────────────────────────────
    //  READ from .env / setting instansi
    // ─────────────────────────────────────
    private function getConfig(): array
    {
        return [
            'auth_url'      => config('satusehat.auth_url',   env('SATUSEHAT_AUTH_URL',   'https://api-satusehat.kemkes.go.id/oauth2/v1')),
            'base_url'      => config('satusehat.base_url',   env('SATUSEHAT_BASE_URL',   'https://api-satusehat.kemkes.go.id/fhir-r4/v1')),
            'client_id'     => config('satusehat.client_id',  env('SATUSEHAT_CLIENT_ID',  '')),
            'client_secret' => config('satusehat.client_secret', env('SATUSEHAT_CLIENT_SECRET', '')),
            'org_id'        => config('satusehat.org_id',     env('SATUSEHAT_ORG_ID',     '')),
        ];
    }

    // ─────────────────────────────────────
    //  PAGE
    // ─────────────────────────────────────
    public function index()
    {
        return view('satusehat.kirim_request');
    }

    // ─────────────────────────────────────
    //  AJAX: TAMPIL DATA dari SIMRS
    //  Logic identik proses_servicerequest_radiologi.php (aksi=tampil)
    //  Gabung Rawat Jalan + Rawat Inap, deduplikasi by noorder+kd_jenis_prw
    // ─────────────────────────────────────
    public function tampil(Request $request)
    {
        $tgl1    = $request->input('tgl1', date('Y-m-d'));
        $tgl2    = $request->input('tgl2', date('Y-m-d'));
        $keyword = trim($request->input('keyword', ''));

        // Validasi tanggal
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl1)) $tgl1 = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl2)) $tgl2 = date('Y-m-d');

        $params = [$tgl1, $tgl2];
        $kwSql  = '';

        if ($keyword !== '') {
            $kwSql = " AND (
                reg_periksa.no_rawat                 LIKE ?  OR
                reg_periksa.no_rkm_medis             LIKE ?  OR
                pasien.nm_pasien                     LIKE ?  OR
                pasien.no_ktp                        LIKE ?  OR
                pegawai.nama                         LIKE ?  OR
                jns_perawatan_radiologi.nm_perawatan LIKE ?  OR
                satu_sehat_mapping_radiologi.code    LIKE ?  OR
                permintaan_radiologi.noorder         LIKE ?
            )";
            $kw = "%{$keyword}%";
            array_push($params, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
        }

        $sqlBase = "
            SELECT
                reg_periksa.no_rawat,
                reg_periksa.no_rkm_medis,
                pasien.nm_pasien,
                IFNULL(pasien.no_ktp,'')                                            AS no_ktp_pasien,
                reg_periksa.kd_dokter,
                IFNULL(pegawai.nama,'-')                                            AS nama_dokter,
                IFNULL(pegawai.no_ktp,'')                                           AS no_ktp_dokter,
                satu_sehat_encounter.id_encounter,
                permintaan_radiologi.noorder,
                permintaan_radiologi.tgl_permintaan,
                permintaan_radiologi.jam_permintaan,
                IFNULL(permintaan_radiologi.diagnosa_klinis,'')                     AS diagnosa_klinis,
                jns_perawatan_radiologi.nm_perawatan,
                satu_sehat_mapping_radiologi.code,
                satu_sehat_mapping_radiologi.system,
                satu_sehat_mapping_radiologi.display,
                IFNULL(satu_sehat_servicerequest_radiologi.id_servicerequest,'')    AS id_servicerequest,
                permintaan_pemeriksaan_radiologi.kd_jenis_prw
            FROM reg_periksa
            INNER JOIN pasien
                ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            INNER JOIN pegawai
                ON pegawai.nik = reg_periksa.kd_dokter
            INNER JOIN satu_sehat_encounter
                ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat
            INNER JOIN permintaan_radiologi
                ON permintaan_radiologi.no_rawat = reg_periksa.no_rawat
            INNER JOIN permintaan_pemeriksaan_radiologi
                ON permintaan_pemeriksaan_radiologi.noorder = permintaan_radiologi.noorder
            INNER JOIN jns_perawatan_radiologi
                ON jns_perawatan_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
            INNER JOIN satu_sehat_mapping_radiologi
                ON satu_sehat_mapping_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
            LEFT JOIN satu_sehat_servicerequest_radiologi
                ON  satu_sehat_servicerequest_radiologi.noorder      = permintaan_pemeriksaan_radiologi.noorder
                AND satu_sehat_servicerequest_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
        ";

        try {
            $rows = [];
            $seen = [];

            // Rawat Jalan
            $sqlRJ = $sqlBase . "
                WHERE DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
                AND reg_periksa.status_lanjut = 'Ralan'
                {$kwSql}
                ORDER BY permintaan_radiologi.noorder DESC
            ";
            $resRJ = DB::connection('simrs')->select($sqlRJ, $params);
            foreach ($resRJ as $r) {
                $r = (array) $r;
                $key = $r['noorder'] . '|' . $r['kd_jenis_prw'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $r['tgl_jam'] = $r['tgl_permintaan'] . ' ' . $r['jam_permintaan'];
                $rows[] = $r;
            }

            // Rawat Inap
            $sqlRI = $sqlBase . "
                WHERE DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
                AND reg_periksa.status_lanjut = 'Ranap'
                {$kwSql}
                ORDER BY permintaan_radiologi.noorder DESC
            ";
            $resRI = DB::connection('simrs')->select($sqlRI, $params);
            foreach ($resRI as $r) {
                $r = (array) $r;
                $key = $r['noorder'] . '|' . $r['kd_jenis_prw'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $r['tgl_jam'] = $r['tgl_permintaan'] . ' ' . $r['jam_permintaan'];
                $rows[] = $r;
            }

            return response()->json(['ok' => true, 'data' => $rows]);

        } catch (\Exception $e) {
            Log::error('KirimRequest tampil error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────
    //  AJAX: GET TOKEN SATUSEHAT
    // ─────────────────────────────────────
    private function getToken(): string
    {
        $cfg = $this->getConfig();
        $response = Http::asForm()->post($cfg['auth_url'] . '/accesstoken?grant_type=client_credentials', [
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ]);
        return $response->json('access_token', '');
    }

    // ─────────────────────────────────────
    //  HELPER: Get Patient FHIR ID via NIK
    // ─────────────────────────────────────
    private function getPatientId(string $nik, string $token): string
    {
        if (empty($nik)) return '';
        $cfg = $this->getConfig();
        $url = rtrim($cfg['base_url'], '/') . '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . urlencode($nik);
        $resp = Http::withToken($token)->get($url);
        return $resp->json('entry.0.resource.id', '');
    }

    // ─────────────────────────────────────
    //  HELPER: Get Practitioner FHIR ID via NIK
    // ─────────────────────────────────────
    private function getPractitionerId(string $nik, string $token): string
    {
        if (empty($nik)) return '';
        $cfg = $this->getConfig();
        $url = rtrim($cfg['base_url'], '/') . '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . urlencode($nik);
        $resp = Http::withToken($token)->get($url);
        return $resp->json('entry.0.resource.id', '');
    }

    // ─────────────────────────────────────
    //  HELPER: Build ServiceRequest FHIR payload
    // ─────────────────────────────────────
    private function buildPayload(array $row, string $idPasien, string $idDokter, ?string $idSr = null): array
    {
        $cfg     = $this->getConfig();
        $acsn    = $row['noorder'];
        $authored = str_replace(' ', 'T', $row['tgl_jam']) . '+07:00';
        $displayEncounter = 'Permintaan ' . $row['nm_perawatan']
            . ' atas nama pasien ' . $row['nm_pasien']
            . ' No.RM ' . $row['no_rkm_medis']
            . ' No.Rawat ' . $row['no_rawat']
            . ', pada tanggal ' . $row['tgl_jam'];

        $payload = [
            'resourceType' => 'ServiceRequest',
            'identifier'   => [[
                'system' => 'http://sys-ids.kemkes.go.id/acsn/' . $cfg['org_id'],
                'value'  => $acsn,
            ]],
            'status'   => 'active',
            'intent'   => 'order',
            'category' => [[
                'coding' => [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '363679005',
                    'display' => 'Imaging',
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system'  => $row['system'],
                    'code'    => $row['code'],
                    'display' => $row['display'],
                ]],
                'text' => $row['nm_perawatan'],
            ],
            'subject'    => ['reference' => 'Patient/' . $idPasien],
            'encounter'  => [
                'reference' => 'Encounter/' . $row['id_encounter'],
                'display'   => $displayEncounter,
            ],
            'authoredOn' => $authored,
            'requester'  => [
                'reference' => 'Practitioner/' . $idDokter,
                'display'   => $row['nama_dokter'],
            ],
            'performer'  => [[
                'reference' => 'Organization/' . $cfg['org_id'],
                'display'   => 'Ruang Radiologi/Petugas Radiologi',
            ]],
            'reasonCode' => [[
                'text' => $row['diagnosa_klinis'],
            ]],
        ];

        if ($idSr) $payload['id'] = $idSr;

        return $payload;
    }

    // ─────────────────────────────────────
    //  HELPER: Ambil detail row dari DB
    // ─────────────────────────────────────
    private function fetchRowFromDb(string $noorder, string $kdJenisPrw): ?array
    {
        $sql = "
            SELECT
                reg_periksa.no_rawat, reg_periksa.no_rkm_medis,
                pasien.nm_pasien,
                permintaan_radiologi.noorder,
                permintaan_radiologi.tgl_permintaan, permintaan_radiologi.jam_permintaan,
                IFNULL(permintaan_radiologi.diagnosa_klinis,'') AS diagnosa_klinis,
                jns_perawatan_radiologi.nm_perawatan,
                satu_sehat_mapping_radiologi.code,
                satu_sehat_mapping_radiologi.system,
                satu_sehat_mapping_radiologi.display,
                pegawai.nama AS nama_dokter,
                satu_sehat_encounter.id_encounter
            FROM permintaan_radiologi
            INNER JOIN permintaan_pemeriksaan_radiologi
                ON permintaan_pemeriksaan_radiologi.noorder = permintaan_radiologi.noorder
                AND permintaan_pemeriksaan_radiologi.kd_jenis_prw = ?
            INNER JOIN jns_perawatan_radiologi
                ON jns_perawatan_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw
            INNER JOIN satu_sehat_mapping_radiologi
                ON satu_sehat_mapping_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
            INNER JOIN reg_periksa
                ON reg_periksa.no_rawat = permintaan_radiologi.no_rawat
            INNER JOIN pasien
                ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            INNER JOIN pegawai
                ON pegawai.nik = reg_periksa.kd_dokter
            INNER JOIN satu_sehat_encounter
                ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat
            WHERE permintaan_radiologi.noorder = ?
            LIMIT 1
        ";
        $res = DB::connection('simrs')->select($sql, [$kdJenisPrw, $noorder]);
        if (empty($res)) return null;
        $row = (array) $res[0];
        $row['tgl_jam'] = $row['tgl_permintaan'] . ' ' . $row['jam_permintaan'];
        return $row;
    }

    // ─────────────────────────────────────
    //  HELPER: Save/Update ke DB SIMRS
    // ─────────────────────────────────────
    private function saveToSimrs(string $noorder, string $kdJenisPrw, string $idSr): void
    {
        $exists = DB::connection('simrs')
            ->table('satu_sehat_servicerequest_radiologi')
            ->where('noorder', $noorder)
            ->where('kd_jenis_prw', $kdJenisPrw)
            ->exists();

        if ($exists) {
            DB::connection('simrs')
                ->table('satu_sehat_servicerequest_radiologi')
                ->where('noorder', $noorder)
                ->where('kd_jenis_prw', $kdJenisPrw)
                ->update(['id_servicerequest' => $idSr]);
        } else {
            DB::connection('simrs')
                ->table('satu_sehat_servicerequest_radiologi')
                ->insert(['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw, 'id_servicerequest' => $idSr]);
        }
    }

    // ─────────────────────────────────────
    //  AJAX: POST – kirim ServiceRequest baru
    // ─────────────────────────────────────
    public function post(Request $request)
    {
        $logs = [];
        $addLog = function (string $type, string $msg) use (&$logs) {
            $logs[] = ['type' => $type, 'msg' => $msg];
        };

        $noorder      = trim($request->input('noorder', ''));
        $kdJenisPrw   = trim($request->input('kd_jenis_prw', ''));
        $nikPasien    = trim($request->input('no_ktp_pasien', ''));
        $nikDokter    = trim($request->input('no_ktp_dokter', ''));
        $idEncounter  = trim($request->input('id_encounter', ''));

        if (!$noorder || !$kdJenisPrw || !$nikPasien || !$nikDokter || !$idEncounter) {
            return response()->json(['ok' => false, 'msg' => 'Parameter tidak lengkap.', 'logs' => $logs]);
        }

        try {
            $row = $this->fetchRowFromDb($noorder, $kdJenisPrw);
            if (!$row) return response()->json(['ok' => false, 'msg' => 'Data tidak ditemukan di DB.', 'logs' => $logs]);

            $row['id_encounter'] = $idEncounter;

            $token = $this->getToken();
            if (!$token) return response()->json(['ok' => false, 'msg' => 'Gagal mendapatkan token SatuSehat.', 'logs' => $logs]);

            $addLog('info', "Mendapatkan Patient ID via NIK: {$nikPasien}");
            $idPasien = $this->getPatientId($nikPasien, $token);
            if (!$idPasien) return response()->json(['ok' => false, 'msg' => 'Patient ID tidak ditemukan di SatuSehat. Cek NIK pasien.', 'logs' => $logs]);
            $addLog('ok', "Patient ID: {$idPasien}");

            $addLog('info', "Mendapatkan Practitioner ID via NIK dokter: {$nikDokter}");
            $idDokter = $this->getPractitionerId($nikDokter, $token);
            if (!$idDokter) return response()->json(['ok' => false, 'msg' => 'Practitioner ID tidak ditemukan di SatuSehat. Cek NIK dokter.', 'logs' => $logs]);
            $addLog('ok', "Practitioner ID: {$idDokter}");

            $cfg     = $this->getConfig();
            $url     = rtrim($cfg['base_url'], '/') . '/ServiceRequest';
            $payload = $this->buildPayload($row, $idPasien, $idDokter);

            $addLog('info', "POST → {$url}");
            $addLog('info', "ACSN: {$noorder}");

            $resp = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/fhir+json'])
                ->post($url, $payload);

            $result = $resp->json();
            $idSr   = $result['id'] ?? '';

            $addLog('info', "HTTP {$resp->status()} → " . substr($resp->body(), 0, 300));

            if (empty($idSr)) {
                $issue = $result['issue'][0]['diagnostics'] ?? ($result['issue'][0]['details']['text'] ?? $resp->body());
                $addLog('err', "POST gagal: {$issue}");
                return response()->json(['ok' => false, 'msg' => "POST gagal: {$issue}", 'logs' => $logs]);
            }

            $this->saveToSimrs($noorder, $kdJenisPrw, $idSr);
            $addLog('ok', "Berhasil POST ServiceRequest. ID: {$idSr}");

            return response()->json(['ok' => true, 'msg' => 'ServiceRequest berhasil dikirim.', 'id_servicerequest' => $idSr, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            Log::error('KirimRequest POST error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    // ─────────────────────────────────────
    //  AJAX: PUT – update ServiceRequest yang sudah ada
    // ─────────────────────────────────────
    public function put(Request $request)
    {
        $logs = [];
        $addLog = function (string $type, string $msg) use (&$logs) {
            $logs[] = ['type' => $type, 'msg' => $msg];
        };

        $noorder      = trim($request->input('noorder', ''));
        $kdJenisPrw   = trim($request->input('kd_jenis_prw', ''));
        $nikPasien    = trim($request->input('no_ktp_pasien', ''));
        $nikDokter    = trim($request->input('no_ktp_dokter', ''));
        $idEncounter  = trim($request->input('id_encounter', ''));
        $idSr         = trim($request->input('id_servicerequest', ''));

        if (!$noorder || !$kdJenisPrw || !$nikPasien || !$nikDokter || !$idEncounter || !$idSr) {
            return response()->json(['ok' => false, 'msg' => 'Parameter tidak lengkap untuk PUT.', 'logs' => $logs]);
        }

        try {
            $row = $this->fetchRowFromDb($noorder, $kdJenisPrw);
            if (!$row) return response()->json(['ok' => false, 'msg' => 'Data tidak ditemukan di DB.', 'logs' => $logs]);

            $row['id_encounter'] = $idEncounter;

            $token = $this->getToken();
            if (!$token) return response()->json(['ok' => false, 'msg' => 'Gagal mendapatkan token SatuSehat.', 'logs' => $logs]);

            $addLog('info', "Mendapatkan Patient ID via NIK: {$nikPasien}");
            $idPasien = $this->getPatientId($nikPasien, $token);
            if (!$idPasien) return response()->json(['ok' => false, 'msg' => 'Patient ID tidak ditemukan di SatuSehat.', 'logs' => $logs]);
            $addLog('ok', "Patient ID: {$idPasien}");

            $addLog('info', "Mendapatkan Practitioner ID via NIK dokter: {$nikDokter}");
            $idDokter = $this->getPractitionerId($nikDokter, $token);
            if (!$idDokter) return response()->json(['ok' => false, 'msg' => 'Practitioner ID tidak ditemukan di SatuSehat.', 'logs' => $logs]);
            $addLog('ok', "Practitioner ID: {$idDokter}");

            $cfg     = $this->getConfig();
            $url     = rtrim($cfg['base_url'], '/') . '/ServiceRequest/' . $idSr;
            $payload = $this->buildPayload($row, $idPasien, $idDokter, $idSr);

            $addLog('info', "PUT → {$url}");
            $addLog('info', "ACSN: {$noorder}");

            $resp = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/fhir+json'])
                ->put($url, $payload);

            $result   = $resp->json();
            $idResult = $result['id'] ?? '';

            $addLog('info', "HTTP {$resp->status()} → " . substr($resp->body(), 0, 300));

            if (empty($idResult)) {
                $issue = $result['issue'][0]['diagnostics'] ?? ($result['issue'][0]['details']['text'] ?? $resp->body());
                $addLog('err', "PUT gagal: {$issue}");
                return response()->json(['ok' => false, 'msg' => "PUT gagal: {$issue}", 'logs' => $logs]);
            }

            $addLog('ok', "Berhasil PUT ServiceRequest. ID: {$idSr}");

            return response()->json(['ok' => true, 'msg' => 'ServiceRequest berhasil diupdate.', 'id_servicerequest' => $idSr, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            Log::error('KirimRequest PUT error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }
}
