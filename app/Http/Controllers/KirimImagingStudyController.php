<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SatuSehatRadiologiService;

/**
 * KirimImagingStudyController
 *
 * Alur:
 *  0. Cek eksistensi di SatuSehat Cloud → shortcut jika sudah ada
 *  1. Cari DICOM Study di Orthanc
 *  2. Sinkronisasi metadata (AccNo, PatientID, StudyDescription, StudyInstanceUID)
 *     — Series & Instance UID DIPERTAHANKAN (KeepSopInstanceUID + KeepSeriesInstanceUID)
 *  3. Push ke DICOM Router via C-STORE — HANYA jika instance baru / belum pernah terkirim
 *  4. Polling SatuSehat FHIR hingga 5x untuk mendapatkan id_imaging
 *  5. Simpan / Update record ke SIMRS DB
 */
class KirimImagingStudyController extends Controller
{
    private SatuSehatRadiologiService $ss;
    private string $orthancBase;
    private string $orthancUser;
    private string $orthancPass;
    private string $dicomModality;
    private string $fhirBase;

    public function __construct(SatuSehatRadiologiService $ss)
    {
        $this->ss = $ss;
        $this->orthancBase = rtrim(env('PACS_URL', 'http://localhost:8042'), '/');
        $this->orthancUser = env('PACS_USERNAME', 'orthanc');
        $this->orthancPass = env('PACS_PASSWORD', 'orthanc');
        $this->dicomModality = env('DICOM_ROUTER_MODALITY', 'ROUTERDICOM');
        $this->fhirBase = rtrim(config('satusehat.base_url'), '/');
    }

    // ────────────────────────────────────────────
    //  INDEX — daftar data siap kirim Imaging Study
    // ────────────────────────────────────────────

    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_radiologi', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join(
                'permintaan_pemeriksaan_radiologi',
                'permintaan_pemeriksaan_radiologi.noorder',
                '=',
                'permintaan_radiologi.noorder'
            )
            ->join(
                'jns_perawatan_radiologi',
                'jns_perawatan_radiologi.kd_jenis_prw',
                '=',
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw'
            )
            ->join(
                'satu_sehat_mapping_radiologi',
                'satu_sehat_mapping_radiologi.kd_jenis_prw',
                '=',
                'jns_perawatan_radiologi.kd_jenis_prw'
            )
            ->join('satu_sehat_servicerequest_radiologi', function ($j) {
                $j->on('satu_sehat_servicerequest_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                    ->on('satu_sehat_servicerequest_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->leftJoin('satu_sehat_imagingstudy_radiologi', function ($j) {
                $j->on('satu_sehat_imagingstudy_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                    ->on('satu_sehat_imagingstudy_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'reg_periksa.kd_dokter',
                'pegawai.nama as nama_dokter',
                'satu_sehat_encounter.id_encounter',
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.tgl_permintaan',
                'permintaan_radiologi.jam_permintaan',
                'permintaan_radiologi.tgl_sampel',
                'permintaan_radiologi.jam_sampel',
                'permintaan_radiologi.diagnosa_klinis',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.code',
                'satu_sehat_mapping_radiologi.system',
                'satu_sehat_mapping_radiologi.display',
                'satu_sehat_servicerequest_radiologi.id_servicerequest',
                DB::raw('IFNULL(satu_sehat_imagingstudy_radiologi.id_imaging,"") AS id_imaging'),
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw'
            )
            ->whereBetween('permintaan_radiologi.tgl_permintaan', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                    ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                    ->orWhere('permintaan_radiologi.noorder', 'like', "%$keyword%");
            });
        }

        $perPage = $request->get('per_page', 25);
        $queryPagination = $query->orderBy('permintaan_radiologi.noorder', 'desc');

        $orders = ($perPage === 'all')
            ? $queryPagination->paginate(1000000)->withQueryString()
            : $queryPagination->paginate((int) $perPage)->withQueryString();

        return view('satusehat.kirim_imaging_study', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    // ────────────────────────────────────────────
    //  POST — proses pengiriman
    // ────────────────────────────────────────────

    public function post(Request $request)
    {
        $logs = [];
        $addLog = function (string $type, string $msg) use (&$logs) {
            $ts = date('H:i:s');
            $logs[] = ['type' => $type, 'msg' => "[{$ts}] {$msg}"];
            Log::info("KirimImagingStudy [{$type}] {$msg}");
        };

        try {
            $data = $request->all();
            $noorder = trim($data['noorder'] ?? '');
            $kdJenisPrw = trim($data['kd_jenis_prw'] ?? '');
            $idSR = trim($data['id_servicerequest'] ?? '');
            $noRkmMedis = trim($data['no_rkm_medis'] ?? '');
            $tglPermintaan = trim($data['tgl_permintaan'] ?? '');
            $nmPasien = trim($data['nm_pasien'] ?? '');
            $forceSend = (bool) $request->get('force', false);

            if (!$noorder || !$kdJenisPrw || !$idSR) {
                throw new \Exception('Parameter tidak lengkap: No Order, Jenis Perawatan, dan ID ServiceRequest wajib ada.');
            }

            $acsn = $noorder; // AccessionNumber = noorder

            $addLog('info', 'MEMULAI PROSES — ACSN: ' . $acsn);

            // ── STEP 0: Cek eksistensi di SatuSehat Cloud ──────────────────────
            $addLog('info', 'STEP 0: Mengecek eksistensi di SatuSehat Cloud...');

            $forcedStudyUid = null;
            $idImagingExisting = null;
            $dicomSudahAda = false; // flag: apakah DICOM sudah pernah masuk ke NIDR

            try {
                $token = $this->ss->getToken();
                $orgId = $this->ss->getOrganizationId();
                $systemAcsn = "http://sys-ids.kemkes.go.id/acsn/{$orgId}";
                $checkUrl = $this->fhirBase . '/ImagingStudy?identifier=' . urlencode("{$systemAcsn}|{$acsn}");
                $checkResp = Http::withToken($token)->withoutVerifying()->get($checkUrl);

                if ($checkResp->successful() && $checkResp->json('total', 0) > 0) {
                    $entry = $checkResp->json('entry.0.resource');
                    $idImagingExisting = $entry['id'] ?? null;

                    // Ambil StudyInstanceUID yang sudah terdaftar → wajib dipertahankan
                    foreach (($entry['identifier'] ?? []) as $idnt) {
                        if (($idnt['system'] ?? '') === 'urn:dicom:uid') {
                            $forcedStudyUid = str_replace('urn:oid:', '', $idnt['value']);
                            $addLog('info', 'KONSISTENSI: Menggunakan UID terdaftar (.' . substr($forcedStudyUid, -6) . ')');
                        }
                    }

                    // Cek apakah instance DICOM sudah ada di NIDR
                    $seriesUidCloud = $entry['series'][0]['uid'] ?? null;
                    $instanceUidCloud = $entry['series'][0]['instance'][0]['uid'] ?? null;

                    if ($seriesUidCloud && $instanceUidCloud) {
                        // Coba HEAD ke DICOMweb untuk konfirmasi file ada di NIDR
                        try {
                            $wadoUrl = str_replace('/fhir-r4/v1', '', $this->fhirBase);
                            $instanceUrl = rtrim($wadoUrl, '/') .
                                "/dicom/v1/dicomWeb/studies/{$forcedStudyUid}" .
                                "/series/{$seriesUidCloud}" .
                                "/instances/{$instanceUidCloud}";
                            $headResp = Http::withToken($token)->withoutVerifying()
                                ->withHeaders(['Accept' => 'application/dicom'])
                                ->head($instanceUrl);
                            $dicomSudahAda = $headResp->successful();
                        } catch (\Exception $e) {
                            $dicomSudahAda = false;
                        }
                        $addLog('info', 'DICOM di NIDR: ' . ($dicomSudahAda ? 'SUDAH ADA ✓' : 'BELUM ADA / PERLU UPLOAD'));
                    }

                    // Shortcut: jika tidak di-force dan DICOM sudah ada → return langsung
                    if (!$forceSend && $idImagingExisting && $dicomSudahAda) {
                        $addLog('ok', 'SHORTCUT: Data & file DICOM sudah ada di SatuSehat (ID: ' . $idImagingExisting . '). Proses dihentikan.');
                        $this->saveToDb($noorder, $kdJenisPrw, $idSR, $idImagingExisting);
                        return response()->json([
                            'ok' => true,
                            'id_imaging' => $idImagingExisting,
                            'logs' => $logs,
                            'msg' => 'Data sudah ada. id_imaging: ' . $idImagingExisting,
                        ]);
                    }

                    if ($forceSend) {
                        $addLog('warn', '!!! FORCE SEND AKTIF — Akan upload ulang ke SatuSehat !!!');
                    } elseif ($idImagingExisting && !$dicomSudahAda) {
                        $addLog('warn', 'ImagingStudy ada di FHIR tapi file DICOM belum terkonfirmasi di NIDR — lanjut upload.');
                    }
                } else {
                    $addLog('info', 'Belum ada data di SatuSehat — proses upload penuh.');
                }
            } catch (\Exception $e) {
                $addLog('info', 'Pengecekan cloud selesai (exception: ' . $e->getMessage() . ').');
            }

            // ── STEP 1: Cari Study di Orthanc ──────────────────────────────────
            $addLog('info', 'STEP 1: Mencari study di Orthanc...');
            $studies = [];
            $dicomDate = str_replace('-', '', $tglPermintaan);

            // Strategi 1: PatientID + StudyDate
            $r = $this->orthancPost('/tools/find', [
                'Level' => 'Study',
                'Expand' => true,
                'Query' => ['PatientID' => $noRkmMedis, 'StudyDate' => $dicomDate],
            ]);
            if ($r !== false)
                $studies = json_decode($r, true) ?: [];
            if ($studies)
                $addLog('info', '[1] Ditemukan via PatientID+StudyDate: ' . count($studies) . ' study');

            // Strategi 2: AccessionNumber
            if (!$studies) {
                $r = $this->orthancPost('/tools/find', [
                    'Level' => 'Study',
                    'Expand' => true,
                    'Query' => ['AccessionNumber' => $noorder],
                ]);
                if ($r !== false)
                    $studies = json_decode($r, true) ?: [];
                if ($studies)
                    $addLog('info', '[2] Ditemukan via AccessionNumber');
            }

            // Strategi 3: StudyInstanceUID (jika forcedStudyUid diketahui)
            if (!$studies && $forcedStudyUid) {
                $r = $this->orthancPost('/tools/find', [
                    'Level' => 'Study',
                    'Expand' => true,
                    'Query' => ['StudyInstanceUID' => $forcedStudyUid],
                ]);
                if ($r !== false)
                    $studies = json_decode($r, true) ?: [];
                if ($studies)
                    $addLog('info', '[3] Ditemukan via StudyInstanceUID');
            }

            if (!$studies) {
                throw new \Exception('Study DICOM tidak ditemukan di Orthanc (semua strategi gagal).');
            }

            $orthancStudyId = $studies[0]['ID'];
            $studyUid = $studies[0]['MainDicomTags']['StudyInstanceUID'] ?? '';
            $addLog('info', 'Study ditemukan — Orthanc ID: ' . $orthancStudyId);
            $addLog('info', 'StudyInstanceUID saat ini: ' . $studyUid);

            // ── STEP 2: Sinkronisasi Metadata DICOM ────────────────────────────
            $addLog('info', 'STEP 2: Sinkronisasi metadata DICOM...');

            $currentTags = $studies[0]['MainDicomTags'] ?? [];
            $targetRM   = $noRkmMedis;
            $targetAcsn = $acsn;

            // Perbandingan tag dasar
            $isMatch = (($currentTags['AccessionNumber'] ?? '') === $targetAcsn) &&
                       (($currentTags['PatientID']       ?? '') === $targetRM);

            if ($forcedStudyUid && $studyUid !== $forcedStudyUid) $isMatch = false;

            if ($isMatch) {
                $addLog('ok', 'METADATA MATCH: Melewati modify DICOM.');
                $pushStudyId = $orthancStudyId;
            } else {
                $addLog('warn', 'METADATA MISMATCH: Memulai sinkronisasi tag DICOM...');
                $simrsItems = DB::connection('simrs')
                    ->table('permintaan_pemeriksaan_radiologi')
                    ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                    ->where('noorder', $noorder)
                    ->pluck('jns_perawatan_radiologi.nm_perawatan')
                    ->implode(', ');

                $replaceTags = [
                    'AccessionNumber'  => $targetAcsn,
                    'PatientID'        => $targetRM,
                    'PatientName'      => strtoupper($nmPasien),
                    'StudyDescription' => $simrsItems ?: 'Radiology Study',
                ];
                if ($forcedStudyUid && $forcedStudyUid !== $studyUid) $replaceTags['StudyInstanceUID'] = $forcedStudyUid;

                $modRaw = $this->orthancPost('/studies/' . $orthancStudyId . '/modify', [
                    'Replace' => $replaceTags, 'Force' => true, 'KeepSource' => false,
                    'KeepSopInstanceUID' => true, 'KeepSeriesInstanceUID' => true
                ]);

                if ($modRaw === false) throw new \Exception('Modify Orthanc gagal.');
                $modResp = json_decode($modRaw, true);
                $pushStudyId = $modResp['ID'] ?? $orthancStudyId;
                $studyUid = $modResp['MainDicomTags']['StudyInstanceUID'] ?? $studyUid;

                if ($pushStudyId !== $orthancStudyId) $this->orthancDelete('/studies/' . $orthancStudyId);
                $addLog('ok', 'Metadata diselaraskan. StudyUID: ' . $studyUid);
            }

            // Verifikasi Series & Instance UID setelah modify
            $studyDetail = json_decode($this->orthancPost('/studies/' . $pushStudyId, []) ?: '{}', true);
            $seriesId = $studyDetail['Series'][0] ?? null;
            if ($seriesId) {
                $seriesDetail = json_decode($this->orthancGet('/series/' . $seriesId) ?: '{}', true);
                $seriesUid = $seriesDetail['MainDicomTags']['SeriesInstanceUID'] ?? '-';
                $instanceId = $seriesDetail['Instances'][0] ?? null;
                $instanceDetail = $instanceId
                    ? json_decode($this->orthancGet('/instances/' . $instanceId) ?: '{}', true)
                    : [];
                $sopInstanceUid = $instanceDetail['MainDicomTags']['SOPInstanceUID'] ?? '-';

                $addLog('info', 'SeriesInstanceUID  : ' . $seriesUid);
                $addLog('info', 'SOPInstanceUID     : ' . $sopInstanceUid);
            }

            // ── STEP 3: Push ke DICOM Router via C-STORE ───────────────────────
            // Lewati jika DICOM sudah dikonfirmasi ada di NIDR dan tidak force
            if ($dicomSudahAda && !$forceSend) {
                $addLog('info', 'STEP 3: SKIP — File DICOM sudah terkonfirmasi ada di NIDR SatuSehat.');
            } else {
                $addLog('info', 'STEP 3: Push study ke DICOM Router (' . $this->dicomModality . ') via C-STORE...');
                $pushRaw = $this->orthancPost('/modalities/' . $this->dicomModality . '/store', [$pushStudyId]);
                $pushResp = json_decode($pushRaw ?: '{}', true);
                $addLog('info', 'Response DICOM Router: ' . $pushRaw);

                // Orthanc store response menggunakan key "InstancesCount" dan "FailedInstancesCount"
                $instancesSent = $pushResp['InstancesCount'] ?? 0;
                $instancesFailed = $pushResp['FailedInstancesCount'] ?? 0;

                if ($instancesFailed > 0) {
                    $addLog('err', 'DICOM ROUTER: ' . $instancesFailed . ' instance GAGAL dikirim!');
                } elseif ($instancesSent > 0) {
                    $addLog('ok', 'DICOM terkirim ke Router: ' . $instancesSent . ' instance.');
                } else {
                    $addLog('info', 'DICOM Router: 0 instance baru (kemungkinan sudah ada di cache router).');
                }
            }

            // ── STEP 4: Polling SatuSehat FHIR untuk id_imaging ────────────────
            // DICOM Router resmi akan membuat resource ImagingStudy secara otomatis.
            // Kita cukup polling sampai ID tersebut muncul di Cloud SatuSehat.
            $addLog('info', 'STEP 4: Menunggu DICOM Router sinkron ke Cloud (Polling id_imaging)...');
            $idImagingResult = '';

            try {
                $token = $this->ss->getToken();
                $orgId = $this->ss->getOrganizationId();

                for ($attempt = 1; $attempt <= 10; $attempt++) {
                    $addLog('info', "Sinkronisasi Cloud... Percobaan {$attempt}/10 (tunggu 5 detik)");
                    sleep(5);

                    // Cara 1: basedOn ServiceRequest
                    $urlSR = $this->fhirBase . '/ImagingStudy?basedOn=ServiceRequest/' . urlencode($idSR);
                    $r1 = Http::withToken($token)->withoutVerifying()
                        ->withHeaders(['Accept' => 'application/fhir+json'])
                        ->get($urlSR);
                    $idImagingResult = $r1->json('entry.0.resource.id', '');

                    if ($idImagingResult) {
                        $addLog('ok', "SUKSES: DICOM Router telah membuat ImagingStudy: {$idImagingResult}");
                        break;
                    }

                    // Cara 2: identifier AccessionNumber
                    $systemAcsn = "http://sys-ids.kemkes.go.id/acsn/{$orgId}";
                    $urlAcsn = $this->fhirBase . '/ImagingStudy?identifier=' . urlencode("{$systemAcsn}|{$acsn}");
                    $r2 = Http::withToken($token)->withoutVerifying()
                        ->withHeaders(['Accept' => 'application/fhir+json'])
                        ->get($urlAcsn);
                    $idImagingResult = $r2->json('entry.0.resource.id', '');

                    if ($idImagingResult) {
                        $addLog('ok', "SUKSES: ImagingStudy ditemukan via AccessionNumber: {$idImagingResult}");
                        break;
                    }

                    if ($attempt == 10) {
                        $addLog('warn', 'Cloud belum sinkron. Harap cek dashboard DICOM Router atau jalankan sync manual nanti.');
                    }
                }

            } catch (\Exception $e) {
                $addLog('warn', 'Koneksi FHIR saat polling terganggu: ' . $e->getMessage());
            }

            // ── STEP 5: Simpan ke SIMRS DB ─────────────────────────────────────
            $this->saveToDb($noorder, $kdJenisPrw, $idSR, $idImagingResult ?: ($idImagingExisting ?: ''));
            $addLog('ok', 'Alur proses selesai dilakukan.');

            return response()->json([
                'ok' => true,
                'id_imaging' => $idImagingResult ?: $idImagingExisting,
                'study_uid' => $studyUid,
                'acsn' => $acsn,
                'msg' => $idImagingResult
                    ? 'Proses Berhasil. Gambar sudah sinkron di SatuSehat.'
                    : 'Data masuk antrean DICOM Router. Silakan cek status di Aplikasi Mobile pasien beberapa saat lagi.',
                'logs' => $logs,
            ]);

        } catch (\Exception $e) {
            $logs[] = ['type' => 'err', 'msg' => $e->getMessage()];
            Log::error('KirimImagingStudy error: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
                'logs' => $logs,
            ]);
        }
    }

    // ────────────────────────────────────────────
    //  HELPERS
    // ────────────────────────────────────────────

    /** Simpan / update record ke tabel satu_sehat_imagingstudy_radiologi */
    private function saveToDb(
        string $noorder,
        string $kdJenisPrw,
        string $idSR,
        string $idImaging
    ): void {
        DB::connection('simrs')
            ->table('satu_sehat_imagingstudy_radiologi')
            ->updateOrInsert(
                ['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw],
                ['id_servicerequest' => $idSR, 'id_imaging' => $idImaging]
            );
    }

    /** POST ke Orthanc */
    private function orthancPost(string $path, array|string $payload): string|false
    {
        $body = is_array($payload) ? json_encode($payload) : $payload;
        $ch = curl_init($this->orthancBase . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $this->orthancUser . ':' . $this->orthancPass,
        ]);
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        if ($err)
            Log::warning('Orthanc POST error errno=' . $err . ' path=' . $path);
        return $err ? false : $res;
    }

    /** GET ke Orthanc */
    private function orthancGet(string $path): string|false
    {
        $ch = curl_init($this->orthancBase . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $this->orthancUser . ':' . $this->orthancPass,
        ]);
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        if ($err)
            Log::warning('Orthanc GET error errno=' . $err . ' path=' . $path);
        return $err ? false : $res;
    }

    /** DELETE di Orthanc */
    private function orthancDelete(string $path): void
    {
        $ch = curl_init($this->orthancBase . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $this->orthancUser . ':' . $this->orthancPass,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}