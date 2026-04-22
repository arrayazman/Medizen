<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Patient;
use App\Models\RadiologyOrder;
use App\Models\Doctor;
use App\Models\ExaminationType;
use App\Models\SimrsModalityMap;
use App\Models\RadiologyTemplate;
use App\Services\WorklistService;
use App\Services\SIMRSSyncService;
use App\Helpers\DicomHelper;
use App\Models\RadiologyReport;
use App\Models\RadiologyResult;
use Illuminate\Support\Str;

class SimrsController extends Controller
{
    public function detailPermintaan($noorder, \App\Services\PACSClient $PACS)
    {
        $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
            ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'permintaan_radiologi.dokter_perujuk', '=', 'dokter.kd_dokter')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->select(
                'permintaan_radiologi.*',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.alamat',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->where('permintaan_radiologi.noorder', $noorder)
            ->first();

        if (!$simrsOrder) {
            return redirect()->back()->with('error', 'Permintaan tidak ditemukan di SIMRS');
        }

        $simrsOrder->items = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
            ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
            ->where('noorder', $noorder)
            ->get();

        // Check if order exists in local RIS
        $localOrder = RadiologyOrder::where('order_number', $noorder)->first();

        // Check PACS for images
        $PACSStudy = null;
        $result = $PACS->post('/tools/find', [
            'Level' => 'Study',
            'Query' => ['AccessionNumber' => $noorder],
            'Limit' => 1
        ]);

        // Fallback: search by PatientID and StudyDate if not found by AccessionNumber
        if ((!$result['success'] || empty($result['data'])) && $simrsOrder) {
            $dicomDate = str_replace('-', '', $simrsOrder->tgl_permintaan);
            $result = $PACS->post('/tools/find', [
                'Level' => 'Study',
                'Query' => [
                    'PatientID' => $simrsOrder->no_rkm_medis,
                    'StudyDate' => $dicomDate
                ],
                'Limit' => 1
            ]);
        }

        if ($result['success'] && !empty($result['data'])) {
            $studyId = reset($result['data']);
            $study = $PACS->getStudy($studyId);
            if ($study) {
                $study['SeriesData'] = [];
                foreach ($study['Series'] ?? [] as $seriesId) {
                    $seriesData = $PACS->getSeries($seriesId);
                    if ($seriesData && !empty($seriesData['Instances'])) {
                        $seriesData['_firstInstance'] = reset($seriesData['Instances']);
                        $seriesData['_instanceCount'] = count($seriesData['Instances']);
                        $study['SeriesData'][] = $seriesData;
                    }
                }
                $PACSStudy = $study;
            }
        }

        if ($localOrder) {
            $localOrder->load('result');
            $simrsOrder->local_expertise = $localOrder->result->expertise ?? '';
        } else {
            $simrsOrder->local_expertise = '';
        }

        // Check SIMRS hasil_radiologi
        $simrsOrder->local_expertise_simrs = DB::connection('simrs')->table('hasil_radiologi')
            ->where('no_rawat', $simrsOrder->no_rawat)
            ->where('tgl_periksa', $simrsOrder->tgl_hasil)
            ->where('jam', $simrsOrder->jam_hasil)
            ->value('hasil') ?? '';

        $simrsOrder->has_expertise = !empty($simrsOrder->local_expertise_simrs) || !empty($simrsOrder->local_expertise);
        $simrsOrder->expertise_content = $simrsOrder->local_expertise_simrs ?: $simrsOrder->local_expertise;

        $baseUrl = $PACS->getPublicUrl();
        $pacsModalities = $PACS->getModalities() ?? [];

        if (request()->ajax()) {
            return view('simrs.partials.order_detail_content', compact('simrsOrder', 'localOrder', 'PACSStudy', 'baseUrl', 'pacsModalities'));
        }

        return view('simrs.show', compact('simrsOrder', 'localOrder', 'PACSStudy', 'baseUrl', 'pacsModalities'));
    }

    public function takeSample(Request $request, WorklistService $worklistService, SIMRSSyncService $syncService)
    {
        $noorder = $request->noorder;

        if (!$noorder) {
            return response()->json(['success' => false, 'message' => 'No. Order tidak ditemukan']);
        }

        try {
            // 1. Get data from SIMRS
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->join('dokter', 'permintaan_radiologi.dokter_perujuk', '=', 'dokter.kd_dokter')
                ->select(
                    'permintaan_radiologi.*',
                    'reg_periksa.no_rkm_medis',
                    'pasien.nm_pasien',
                    'pasien.jk',
                    'pasien.tgl_lahir',
                    'pasien.alamat',
                    'dokter.nm_dokter'
                )
                ->where('permintaan_radiologi.noorder', $noorder)
                ->first();

            if (!$simrsOrder) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan di SIMRS']);
            }

            // 2. Sync Patient
            $patient = Patient::updateOrCreate(
                ['no_rm' => $simrsOrder->no_rkm_medis],
                [
                    'nama' => $simrsOrder->nm_pasien,
                    'jenis_kelamin' => $simrsOrder->jk == 'L' ? 'L' : 'P',
                    'tgl_lahir' => $simrsOrder->tgl_lahir,
                    'alamat' => $simrsOrder->alamat,
                ]
            );

            // 3. Sync Doctor (Referring)
            $doctor = Doctor::firstOrCreate(
                ['kd_dokter' => $simrsOrder->dokter_perujuk],
                ['name' => $simrsOrder->nm_dokter, 'is_active' => true]
            );

            // 4. Get Examination Items from SIMRS
            $simrsItems = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                ->where('noorder', $noorder)
                ->get();

            // Note: For now, we take the FIRST item to determine Modality for the order
            $firstItem = $simrsItems->first();

            // 4a. Resolve modality from SimrsModalityMap (new mapping table)
            $resolvedModalityCode = null;
            $examType = null;

            if ($firstItem) {
                $map = SimrsModalityMap::where('kd_jenis_prw', $firstItem->kd_jenis_prw)->first();
                if ($map) {
                    $resolvedModalityCode = $map->modality_code;
                    // If mapping has an examination_type linked, use it
                    if ($map->examination_type_id) {
                        $examType = ExaminationType::with('modality')->find($map->examination_type_id);
                    }
                }
            }

            // Fallback: try to find by simrs_code
            if (!$examType && $firstItem) {
                $examType = ExaminationType::with('modality')
                    ->where('simrs_code', $firstItem->kd_jenis_prw)
                    ->first();
                if (!$examType) {
                    $examType = ExaminationType::with('modality')
                        ->where('name', 'like', '%' . $firstItem->nm_perawatan . '%')
                        ->first();
                }
            }

            // Final modality code resolution
            $modalityCode = $resolvedModalityCode
                ?? ($examType?->modality?->code)
                ?? 'OT';

            // 5. Create/Update Order in RIS
            $order = RadiologyOrder::updateOrCreate(
                ['order_number' => $noorder],
                [
                    'accession_number' => $noorder,
                    'patient_id' => $patient->id,
                    'modality' => $modalityCode,
                    'examination_type_id' => $examType?->id,
                    'referring_doctor_id' => $doctor->id,
                    'radiographer_id' => auth()->user()->radiographer_id ?? null,
                    'study_instance_uid' => DicomHelper::generateStudyInstanceUID(),
                    'scheduled_date' => $simrsOrder->tgl_permintaan,
                    'scheduled_time' => $simrsOrder->jam_permintaan,
                    'status' => RadiologyOrder::STATUS_SAMPLE_TAKEN,
                    'waktu_sample' => now(),
                    'procedure_description' => $simrsItems->pluck('nm_perawatan')->implode(', '),
                    'origin_system' => 'SIMRS',
                    'external_id' => $noorder,
                    'simrs_no_rawat' => $simrsOrder->no_rawat,
                ]
            );

            // 6. Update SIMRS Sample Time
            $syncService->pushSampleTime($order);

            // 7. Send to PACS Worklist
            $worklistSent = $worklistService->sendToPACS($order);

            return response()->json([
                'success' => true,
                'message' => 'Sample berhasil dicatat dan sudah dikirim ke PACS Worklist' . ($worklistSent ? '' : ' (Gagal kirim ke PACS)'),
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function permintaanRadiologi(Request $request, \App\Services\PACSClient $PACS)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $search = $request->get('search');
        $status = $request->get('status', 'all'); // ralan, ranap, all

        $query = DB::connection('simrs')->table('permintaan_radiologi')
            ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'permintaan_radiologi.dokter_perujuk', '=', 'dokter.kd_dokter')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->select(
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'permintaan_radiologi.tgl_permintaan',
                'permintaan_radiologi.jam_permintaan',
                'reg_periksa.kd_pj',
                'penjab.png_jawab',
                'permintaan_radiologi.tgl_sampel',
                'permintaan_radiologi.jam_sampel',
                'permintaan_radiologi.tgl_hasil',
                'permintaan_radiologi.jam_hasil',
                'permintaan_radiologi.dokter_perujuk',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'permintaan_radiologi.informasi_tambahan',
                'permintaan_radiologi.diagnosa_klinis',
                'permintaan_radiologi.status as status_rawat'
            )
            ->whereBetween('permintaan_radiologi.tgl_permintaan', [$startDate, $endDate]);

        if ($status !== 'all') {
            $query->where('permintaan_radiologi.status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('permintaan_radiologi.noorder', 'like', "%$search%")
                    ->orWhere('permintaan_radiologi.no_rawat', 'like', "%$search%")
                    ->orWhere('pasien.nm_pasien', 'like', "%$search%")
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$search%");
            });
        }

        $orders = $query->orderBy('permintaan_radiologi.tgl_permintaan', 'desc')
            ->orderBy('permintaan_radiologi.jam_permintaan', 'desc')
            ->paginate(20);

        // Fetch items and local info for each order
        foreach ($orders as $order) {
            $order->items = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                ->where('permintaan_pemeriksaan_radiologi.noorder', $order->noorder)
                ->select('jns_perawatan_radiologi.kd_jenis_prw', 'jns_perawatan_radiologi.nm_perawatan')
                ->get();

            // Check if order exists in local RIS
            $localOrder = RadiologyOrder::with('result')->where('order_number', $order->noorder)->first();
            $order->local_expertise = $localOrder ? ($localOrder->result->expertise ?? '') : '';

            // Check SIMRS hasil_radiologi
            $order->local_expertise_simrs = DB::connection('simrs')->table('hasil_radiologi')
                ->where('no_rawat', $order->no_rawat)
                ->where('tgl_periksa', $order->tgl_hasil)
                ->where('jam', $order->jam_hasil)
                ->value('hasil') ?? '';

            // Tampilkan dari SIMRS dulu (Prioritaskan SIMRS)
            $order->has_expertise = !empty($order->local_expertise_simrs) || !empty($order->local_expertise);
        }

        $pacsModalities = $PACS->getModalities() ?? [];

        return view('simrs.permintaan', compact('orders', 'startDate', 'endDate', 'search', 'status', 'pacsModalities'));
    }

    public function hasilRadiologi(Request $request)
    {
        $search = $request->get('search');
        $startDate = $request->get('start_date', $search ? '2000-01-01' : date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = DB::connection('simrs')->table('periksa_radiologi')
            ->join('reg_periksa', 'periksa_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('petugas', 'periksa_radiologi.nip', '=', 'petugas.nip')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->join('dokter', 'periksa_radiologi.kd_dokter', '=', 'dokter.kd_dokter')
            ->leftJoin('permintaan_radiologi', function ($join) {
                $join->on('periksa_radiologi.no_rawat', '=', 'permintaan_radiologi.no_rawat')
                    ->on('periksa_radiologi.tgl_periksa', '=', 'permintaan_radiologi.tgl_hasil')
                    ->on('periksa_radiologi.jam', '=', 'permintaan_radiologi.jam_hasil');
            })
            ->select(
                'periksa_radiologi.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.tgl_lahir',
                'pasien.jk',
                'petugas.nama as nama_petugas',
                'periksa_radiologi.tgl_periksa',
                'periksa_radiologi.jam',
                'periksa_radiologi.dokter_perujuk',
                'periksa_radiologi.kd_dokter',
                'penjab.png_jawab',
                'dokter.nm_dokter as dokter_radiologi',
                'permintaan_radiologi.noorder'
            )
            ->whereBetween('periksa_radiologi.tgl_periksa', [$startDate, $endDate]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('periksa_radiologi.no_rawat', 'like', "%$search%")
                    ->orWhere('pasien.nm_pasien', 'like', "%$search%")
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$search%");
            });
        }

        $results = $query->groupBy('periksa_radiologi.no_rawat', 'periksa_radiologi.tgl_periksa', 'periksa_radiologi.jam')
            ->orderBy('periksa_radiologi.tgl_periksa', 'desc')
            ->orderBy('periksa_radiologi.jam', 'desc')
            ->paginate(20);

        // Fetch expertise and images for each result
        foreach ($results as $result) {
            $result->items = DB::connection('simrs')->table('periksa_radiologi')
                ->join('jns_perawatan_radiologi', 'periksa_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                ->where('periksa_radiologi.no_rawat', $result->no_rawat)
                ->where('periksa_radiologi.tgl_periksa', $result->tgl_periksa)
                ->where('periksa_radiologi.jam', $result->jam)
                ->select('jns_perawatan_radiologi.nm_perawatan', 'periksa_radiologi.biaya')
                ->get();

            $result->hasil = DB::connection('simrs')->table('hasil_radiologi')
                ->where('no_rawat', $result->no_rawat)
                ->where('tgl_periksa', $result->tgl_periksa)
                ->where('jam', $result->jam)
                ->value('hasil');

            $result->gambar_count = DB::connection('simrs')->table('gambar_radiologi')
                ->where('no_rawat', $result->no_rawat)
                ->where('tgl_periksa', $result->tgl_periksa)
                ->where('jam', $result->jam)
                ->count();
        }

        return view('simrs.hasil', compact('results', 'startDate', 'endDate', 'search'));
    }

    public function saveExpertise(Request $request, SIMRSSyncService $syncService)
    {
        $request->validate([
            'noorder' => 'required',
            'expertise' => 'required|string',
            'tgl_periksa' => 'nullable|date',
            'jam' => 'nullable'
        ]);

        $noorder = $request->noorder;
        Log::info("SIMRS Expertise Save Attempt: Order $noorder");

        try {
            // 1. Check if order exists in local RIS, if not create it (Auto Sample)
            $order = RadiologyOrder::where('order_number', $noorder)->first();

            if (!$order) {
                // Manual data fetch since we can't easily call takeSample internal JSON response
                $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                    ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                    ->join('dokter', 'permintaan_radiologi.dokter_perujuk', '=', 'dokter.kd_dokter')
                    ->select('permintaan_radiologi.*', 'reg_periksa.no_rkm_medis', 'pasien.nm_pasien', 'pasien.jk', 'pasien.tgl_lahir', 'pasien.alamat', 'dokter.nm_dokter')
                    ->where('permintaan_radiologi.noorder', $noorder)
                    ->first();

                if (!$simrsOrder) {
                    return redirect()->back()->with('error', 'Data tidak ditemukan di SIMRS');
                }

                $patient = Patient::updateOrCreate(
                    ['no_rm' => $simrsOrder->no_rkm_medis],
                    ['nama' => $simrsOrder->nm_pasien, 'jenis_kelamin' => $simrsOrder->jk == 'L' ? 'L' : 'P', 'tgl_lahir' => $simrsOrder->tgl_lahir, 'alamat' => $simrsOrder->alamat]
                );

                $doctor = Doctor::firstOrCreate(
                    ['kd_dokter' => $simrsOrder->dokter_perujuk],
                    ['name' => $simrsOrder->nm_dokter, 'is_active' => true]
                );

                $simrsItems = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                    ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                    ->where('noorder', $noorder)
                    ->get();

                $firstItem = $simrsItems->first();
                $examType = ExaminationType::where('simrs_code', $firstItem->kd_jenis_prw)->first();

                $order = RadiologyOrder::updateOrCreate(
                    ['order_number' => $noorder],
                    [
                        'accession_number' => $noorder,
                        'patient_id' => $patient->id,
                        'modality' => $examType ? $examType->modality->name : 'OT',
                        'examination_type_id' => $examType ? $examType->id : null,
                        'referring_doctor_id' => $doctor->id,
                        'radiographer_id' => auth()->user()->radiographer_id ?? null,
                        'study_instance_uid' => DicomHelper::generateStudyInstanceUID(),
                        'scheduled_date' => $simrsOrder->tgl_permintaan,
                        'scheduled_time' => $simrsOrder->jam_permintaan,
                        'status' => RadiologyOrder::STATUS_SAMPLE_TAKEN,
                        'waktu_sample' => now(),
                        'procedure_description' => $simrsItems->pluck('nm_perawatan')->implode(', '),
                        'origin_system' => 'SIMRS',
                        'external_id' => $noorder,
                        'simrs_no_rawat' => $simrsOrder->no_rawat,
                    ]
                );

                $syncService->pushSampleTime($order);
            }

            // 2. SAVE TO SIMRS FIRST (As requested: "simpan yang di simrs dulu")
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->where('noorder', $noorder)
                ->first();

            if (!$simrsOrder) {
                throw new \Exception('Data permintaan tidak ditemukan di SIMRS untuk sinkronisasi hasil.');
            }

            DB::connection('simrs')->beginTransaction();
            try {
                // Determine timestamps: 1. From request, 2. From existing result, 3. From sample, 4. Current time
                $tgl_pushed = $request->tgl_periksa 
                            ?? (($simrsOrder->tgl_hasil != '0000-00-00') ? $simrsOrder->tgl_hasil : null)
                            ?? (($simrsOrder->tgl_sampel != '0000-00-00') ? $simrsOrder->tgl_sampel : date('Y-m-d'));
                
                $jam_pushed = $request->jam 
                            ?? (($simrsOrder->jam_hasil != '00:00:00') ? $simrsOrder->jam_hasil : null)
                            ?? (($simrsOrder->jam_sampel != '00:00:00') ? $simrsOrder->jam_sampel : date('H:i:s'));

                $orderItems = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                    ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                    ->where('permintaan_pemeriksaan_radiologi.noorder', $simrsOrder->noorder)
                    ->get();

                // Get valid doctor and petugas code
                $doctorKd = auth()->user()->doctor?->kd_dokter;
                if (!$doctorKd) {
                    // Fallback to referring doctor if current user is not a radiologist
                    $doctorKd = $simrsOrder->dokter_perujuk;
                }

                $petugasNip = auth()->user()->radiographer?->nik;
                if (!$petugasNip) {
                    // Fallback to first available radiographer or dummy if none
                    $petugasNip = DB::connection('simrs')->table('petugas')->where('kd_jbtn', 'J015')->value('nip') ?? '123124';
                }

                foreach ($orderItems as $item) {
                    DB::connection('simrs')->table('periksa_radiologi')->updateOrInsert(
                        [
                            'no_rawat' => $simrsOrder->no_rawat,
                            'tgl_periksa' => $tgl_pushed,
                            'jam' => $jam_pushed,
                            'kd_jenis_prw' => $item->kd_jenis_prw
                        ],
                        [
                            'nip' => $petugasNip,
                            'dokter_perujuk' => $simrsOrder->dokter_perujuk,
                            'kd_dokter' => $doctorKd,
                            'bagian_rs' => $item->bagian_rs,
                            'bhp' => $item->bhp,
                            'tarif_perujuk' => $item->tarif_perujuk,
                            'tarif_tindakan_dokter' => $item->tarif_tindakan_dokter,
                            'tarif_tindakan_petugas' => $item->tarif_tindakan_petugas,
                            'kso' => $item->kso,
                            'menejemen' => $item->menejemen,
                            'biaya' => $item->total_byr,
                            'status' => ucfirst($simrsOrder->status) // normalize to Ralan/Ranap
                        ]
                    );
                }

                DB::connection('simrs')->table('hasil_radiologi')->updateOrInsert(
                    [
                        'no_rawat' => $simrsOrder->no_rawat,
                        'tgl_periksa' => $tgl_pushed,
                        'jam' => $jam_pushed
                    ],
                    ['hasil' => $request->expertise]
                );

                DB::connection('simrs')->table('permintaan_radiologi')
                    ->where('noorder', $simrsOrder->noorder)
                    ->update(['tgl_hasil' => $tgl_pushed, 'jam_hasil' => $jam_pushed]);

                Log::info("SIMRS Expertise Saved Successfully: Order $noorder at $tgl_pushed $jam_pushed");
                DB::connection('simrs')->commit();
            } catch (\Exception $e) {
                DB::connection('simrs')->rollBack();
                Log::error("SIMRS Expertise Save Failed: " . $e->getMessage());
                throw new \Exception('Gagal menyimpan ke SIMRS: ' . $e->getMessage());
            }

            // 3. SAVE TO RIS SECONDLY (As requested: "baru di ris")
            DB::beginTransaction();
            try {
                RadiologyResult::updateOrCreate(
                    ['radiology_order_id' => $order->id],
                    [
                        'expertise' => $request->expertise,
                        'waktu_hasil' => now(),
                        'status' => 'FINAL',
                        'doctor_id' => auth()->user()->doctor_id ?? null
                    ]
                );

                RadiologyReport::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'hasil' => $request->expertise,
                        'kesimpulan' => '-',
                        'dokter_id' => auth()->user()->doctor_id ?? null,
                        'status' => 'VALIDATED',
                        'validated_at' => now()
                    ]
                );

                $order->update(['status' => RadiologyOrder::STATUS_VALIDATED]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                // We don't throw here to avoid confusing the user since SIMRS is already updated
                Log::error('Expertise saved to SIMRS but failed to save to RIS: ' . $e->getMessage());
                return redirect()->back()->with('success', 'Expertise berhasil disimpan ke SIMRS')->with('warning', 'Peringatan: Gagal sinkron ke database lokal RIS.');
            }

            return redirect()->back()->with('success', 'Expertise berhasil disimpan ke SIMRS dan RIS');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getPACSImages($noorder, \App\Services\PACSClient $PACS)
    {
        $result = $PACS->post('/tools/find', [
            'Level' => 'Instance',
            'Query' => ['AccessionNumber' => $noorder],
            'Limit' => 12
        ]);

        // Fallback search if no images found by AccessionNumber
        if ($result['success'] && empty($result['data'])) {
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                ->where('permintaan_radiologi.noorder', $noorder)
                ->select('reg_periksa.no_rkm_medis', 'permintaan_radiologi.tgl_permintaan')
                ->first();

            if ($simrsOrder) {
                $dicomDate = str_replace('-', '', $simrsOrder->tgl_permintaan);
                $result = $PACS->post('/tools/find', [
                    'Level' => 'Instance',
                    'Query' => [
                        'PatientID' => $simrsOrder->no_rkm_medis,
                        'StudyDate' => $dicomDate
                    ],
                    'Limit' => 12
                ]);
            }
        }

        $images = [];
        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as $instanceId) {
                $images[] = route('pacs.instance-preview', $instanceId);
            }
        }

        return response()->json(['success' => true, 'images' => $images]);
    }

    public function updatePACSAccession(Request $request, \App\Services\PACSClient $PACS, \App\Services\SatuSehatRadiologiService $ssRadiologi)
    {
        $noorder = $request->noorder;
        if (!$noorder) return response()->json(['success' => false, 'message' => 'No Order tidak valid']);

        try {
            // 1. Get COMPLETE info from SIMRS
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->where('permintaan_radiologi.noorder', $noorder)
                ->select('permintaan_radiologi.*', 'reg_periksa.no_rkm_medis', 'pasien.nm_pasien')
                ->first();

            if (!$simrsOrder) throw new \Exception('Data permintaan tidak ditemukan di SIMRS');

            // 1b. Get examination items
            $simrsItems = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                ->where('noorder', $noorder)
                ->pluck('jns_perawatan_radiologi.nm_perawatan')
                ->implode(', ');

            // 2. Find Study in PACS (using patient ID)
            $studies = $PACS->findStudies(['PatientID' => $simrsOrder->no_rkm_medis]) ?? [];
            if (empty($studies)) throw new \Exception('Study tidak ditemukan di PACS untuk pasien ini');

            $orthancStudyId = null;
            foreach ($studies as $study) {
                if (($study['MainDicomTags']['AccessionNumber'] ?? '') === $noorder) {
                    $orthancStudyId = $study['ID'];
                    break;
                }
            }
            if (!$orthancStudyId) $orthancStudyId = $studies[0]['ID'];

            // 3. Smart Sync: Check SatuSehat for existing UID
            $forcedStudyUid = null;
            try {
                $orgId = config('satusehat.organization_id');
                $systemAcsn = "http://sys-ids.kemkes.go.id/acsn/{$orgId}";
                $token = $ssRadiologi->getToken();
                $checkResp = \Illuminate\Support\Facades\Http::withToken($token)
                    ->withoutVerifying()
                    ->get(config('satusehat.fhir_url') . '/ImagingStudy?identifier=' . urlencode($systemAcsn . '|' . $noorder));
                
                if ($checkResp->successful()) {
                    $existingIds = $checkResp->json('entry.0.resource.identifier');
                    if ($existingIds) {
                        foreach ($existingIds as $idnt) {
                            if (($idnt['system'] ?? '') === 'urn:dicom:uid') {
                                $forcedStudyUid = str_replace('urn:oid:', '', $idnt['value']);
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) { /* Ignore SS errors for basic sync */ }

            // 4. Perform Metadata Correction
            $replaceTags = [
                'AccessionNumber'  => $noorder,
                'PatientID'        => $simrsOrder->no_rkm_medis,
                'PatientName'      => strtoupper($simrsOrder->nm_pasien),
                'StudyDescription' => $simrsItems ?: 'Radiology Study'
            ];
            if ($forcedStudyUid) $replaceTags['StudyInstanceUID'] = $forcedStudyUid;

            $modResult = $PACS->post("/studies/{$orthancStudyId}/modify", [
                'Replace'               => $replaceTags,
                'Force'                 => true,
                'KeepSource'            => false,
                'KeepSopInstanceUID'    => true,
                'KeepSeriesInstanceUID' => true
            ]);

            if (!$modResult['success']) throw new \Exception('Gagal melakukan sinkronisasi metadata di Orthanc');

            return response()->json([
                'success' => true,
                'message' => 'PACS & SIMRS Sinkron' . ($forcedStudyUid ? ' (Smart Sync UID Aktif)' : '')
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: Search radiology templates (for SIMRS modals)
     */
    public function apiTemplates(Request $request)
    {
        $term = $request->query('q', '');
        $templates = RadiologyTemplate::when($term, function ($query) use ($term) {
            $query->where('template_number', 'like', "%{$term}%")
                ->orWhere('examination_name', 'like', "%{$term}%");
        })
            ->orderBy('examination_name')
            ->limit(30)
            ->get(['id', 'template_number', 'examination_name', 'expertise']);

        return response()->json($templates);
    }

    public function sendOrderToModality(Request $request, \App\Services\PACSClient $PACS)
    {
        try {
            $noorder = $request->noorder;
            $target = $request->target ?? 'dicomrouter'; // default to dicomrouter

            if (!$noorder) {
                return response()->json(['success' => false, 'message' => 'No. Order tidak valid.']);
            }

            // 1. Dapatkan info permintaan dari SIMRS
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->where('noorder', $noorder)
                ->first();

            if (!$simrsOrder) {
                return response()->json(['success' => false, 'message' => 'Permintaan tidak ditemukan di SIMRS.']);
            }

            // 2. Cari Study di Orthanc (Coba No. Order dulu, baru fallback ke RM + Tanggal)
            $studies = $PACS->findStudies(['AccessionNumber' => $noorder]) ?? [];
            
            if (empty($studies)) {
                $query = [
                    'PatientID' => $simrsOrder->no_rkm_medis,
                    'StudyDate' => str_replace('-', '', $simrsOrder->tgl_permintaan)
                ];
                $studies = $PACS->findStudies($query) ?? [];
            }

            if (empty($studies)) {
                return response()->json(['success' => false, 'message' => 'Gambar (Study) tidak ditemukan di PACS untuk permintaan ini.']);
            }

            // Gunakan study pertama yang ditemukan
            $studyId = $studies[0]['ID'];

            // 3. Kirim ke Modality (Timeout tinggi 10 menit)
            set_time_limit(600);
            $uri = "/modalities/" . rawurlencode($target) . "/store";
            $result = $PACS->post($uri, $studyId, 600);
            if ($result['success']) {
                return response()->json([
                    'success' => true, 
                    'message' => "Order {$noorder} berhasil dikirim ke '{$target}'."
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => "Gagal mengirim ke '{$target}': " . ($result['body'] ?? 'Unknown Error')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadDicom(Request $request, \App\Services\PACSClient $PACS, \App\Services\SatuSehatRadiologiService $ssRadiologi)
    {
        $request->validate([
            'noorder' => 'required',
            'dicom_files' => 'required',
        ]);

        $noorder = $request->noorder;

        try {
            // 1. Get info from SIMRS
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                ->where('permintaan_radiologi.noorder', $noorder)
                ->select(
                    'reg_periksa.no_rkm_medis', 
                    'pasien.nm_pasien', 
                    'pasien.tgl_lahir', 
                    'pasien.jk',
                    'permintaan_radiologi.noorder'
                )
                ->first();

            if (!$simrsOrder) throw new \Exception('Data permintaan tidak ditemukan di SIMRS.');

            $files = $request->file('dicom_files');
            if (!$files) throw new \Exception('Tidak ada file yang dipilih.');
            
            $success = 0;
            $failed = 0;

            // Prepare for JPG conversion if needed
            $pythonPath = config('app.python_path', 'python'); // Define in .env if needed
            $scriptPath = base_path('app/Scripts/jpg2dcm.py');
            
            // Try to find existing StudyInstanceUID from PACS for this accession
            $existingStudy = $PACS->findStudyByAccession($noorder);
            $studyUid = $existingStudy['MainDicomTags']['StudyInstanceUID'] ?? \App\Helpers\DicomHelper::generateStudyInstanceUID();

            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $finalFile = $file;
                $tempPath = null;

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    // Convert to DICOM via Python
                    $tempPath = storage_path('app/temp_' . uniqid() . '.dcm');
                    
                    $command = sprintf(
                        '%s %s %s %s %s %s %s %s %s %s',
                        escapeshellarg($pythonPath),
                        escapeshellarg($scriptPath),
                        escapeshellarg($file->getRealPath()),
                        escapeshellarg($tempPath),
                        escapeshellarg($simrsOrder->nm_pasien),
                        escapeshellarg($simrsOrder->no_rkm_medis),
                        escapeshellarg($simrsOrder->tgl_lahir),
                        escapeshellarg($simrsOrder->jk),
                        escapeshellarg($noorder),
                        escapeshellarg($studyUid)
                    );

                    $output = [];
                    $returnVar = 0;
                    exec($command, $output, $returnVar);

                    if ($returnVar === 0 && file_exists($tempPath)) {
                        $pacsResponse = $PACS->uploadFile($tempPath);
                        unlink($tempPath); // Clean up
                    } else {
                        $pacsResponse = ['success' => false, 'message' => implode(' ', $output)];
                    }
                } else {
                    // Normal DICOM Upload
                    $pacsResponse = $PACS->uploadFile($file);
                }

                if ($pacsResponse['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            }

            // Sync tags to the newly uploaded study with Smart Sync UID
            if ($success > 0) {
                $this->updatePACSAccession($request, $PACS, $ssRadiologi);
            }

            return response()->json([
                'success' => true, 
                'message' => "Upload selesai. Berhasil: $success, Gagal: $failed."
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request)
    {
        $orderId = $request->order_id;
        $status = $request->status; // EXAMINING, COMPLETED

        $order = RadiologyOrder::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order RIS tidak ditemukan.']);
        }

        try {
            $order->update(['status' => $status]);
            return response()->json(['success' => true, 'message' => "Status berhasil diupdate ke $status."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sendToWorklistDirect(Request $request, WorklistService $worklistService)
    {
        $orderId = $request->order_id;
        $targetModality = $request->target;

        $order = RadiologyOrder::find($orderId);
        if (!$order) return response()->json(['success' => false, 'message' => 'Order RIS tidak ditemukan.']);

        try {
            if ($targetModality) {
                $order->update(['modality' => $targetModality]);
            }
            
            $worklistSent = $worklistService->sendToPACS($order);
            
            return response()->json([
                'success' => true, 
                'message' => $worklistSent ? 'Berhasil dikirim ke Worklist PACS.' : 'Gagal mengirim ke Worklist PACS.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function printHasilPdf(Request $request, \App\Services\PACSClient $PACS)
    {
        $no_rawat = $request->query('rawat');
        $tgl = $request->query('tgl');
        $jam = $request->query('jam');

        $simrsData = DB::connection('simrs')->table('periksa_radiologi')
            ->join('reg_periksa', 'periksa_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->leftJoin('dokter as dr_perujuk', 'periksa_radiologi.dokter_perujuk', '=', 'dr_perujuk.kd_dokter')
            ->leftJoin('dokter as dr_rad', 'periksa_radiologi.kd_dokter', '=', 'dr_rad.kd_dokter')
            ->leftJoin('permintaan_radiologi', function ($join) {
                $join->on('periksa_radiologi.no_rawat', '=', 'permintaan_radiologi.no_rawat')
                    ->on('periksa_radiologi.tgl_periksa', '=', 'permintaan_radiologi.tgl_hasil')
                    ->on('periksa_radiologi.jam', '=', 'permintaan_radiologi.jam_hasil');
            })
            ->select(
                'periksa_radiologi.*',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'reg_periksa.no_rkm_medis',
                'dr_perujuk.nm_dokter as perujuk',
                'dr_rad.nm_dokter as dokter_rad',
                'permintaan_radiologi.noorder'
            )
            ->where('periksa_radiologi.no_rawat', $no_rawat)
            ->where('periksa_radiologi.tgl_periksa', $tgl)
            ->where('periksa_radiologi.jam', $jam)
            ->first();

        if (!$simrsData) {
            return back()->with('error', 'Data pemeriksaan tidak ditemukan di SIMRS');
        }

        $hasil = DB::connection('simrs')->table('hasil_radiologi')
            ->where('no_rawat', $no_rawat)
            ->where('tgl_periksa', $tgl)
            ->where('jam', $jam)
            ->value('hasil');

        if (!$hasil) {
            return back()->with('error', 'Hasil/Expertise belum diinput untuk pemeriksaan ini');
        }

        $items = DB::connection('simrs')->table('periksa_radiologi')
            ->join('jns_perawatan_radiologi', 'periksa_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
            ->where('periksa_radiologi.no_rawat', $no_rawat)
            ->where('periksa_radiologi.tgl_periksa', $tgl)
            ->where('periksa_radiologi.jam', $jam)
            ->pluck('jns_perawatan_radiologi.nm_perawatan')
            ->toArray();

        $examName = implode(', ', $items);

        $order = new \App\Models\RadiologyOrder();
        $order->accession_number = $simrsData->noorder ?? '-';
        $order->order_number = $simrsData->noorder ?? '-';
        $order->created_at = clone \Carbon\Carbon::parse($tgl . ' ' . $simrsData->jam);
        $order->waktu_mulai_periksa = $tgl . ' ' . $simrsData->jam;
        $order->modality = 'SIM';

        $patient = new \App\Models\Patient();
        $patient->no_rm = $simrsData->no_rkm_medis;
        $patient->nama = $simrsData->nm_pasien;
        $patient->jenis_kelamin = $simrsData->jk;
        $patient->tgl_lahir = $simrsData->tgl_lahir ? \Carbon\Carbon::parse($simrsData->tgl_lahir) : null;
        $order->setRelation('patient', $patient);

        $exam = new \App\Models\ExaminationType();
        $exam->name = $examName;
        $order->setRelation('examinationType', $exam);

        $ref = new \App\Models\Doctor();
        $ref->name = $simrsData->perujuk ?? '-';
        $order->setRelation('referringDoctor', $ref);

        $res = new \App\Models\RadiologyResult();
        $res->expertise = $hasil;
        $res->waktu_hasil = clone \Carbon\Carbon::parse($tgl . ' ' . $simrsData->jam);

        $docRad = new \App\Models\Doctor();
        $docRad->name = $simrsData->dokter_rad ?? '-';
        $res->setRelation('doctor', $docRad);

        $order->setRelation('result', $res);

        $pdf = new \App\Services\ExaminationPdfService();
        $pdf->generateExpertisePage($order);

        $PACSStudy = null;
        if ($order->accession_number && $order->accession_number !== '-') {
            $resultInfo = $PACS->post('/tools/find', [
                'Level' => 'Study',
                'Query' => ['AccessionNumber' => $order->accession_number],
                'Limit' => 1
            ]);
            if ($resultInfo['success'] && !empty($resultInfo['data'])) {
                $studyId = reset($resultInfo['data']);
                $study = $PACS->getStudy($studyId);
                if ($study) {
                    $study['SeriesData'] = [];
                    foreach ($study['Series'] ?? [] as $seriesId) {
                        $seriesData = $PACS->getSeries($seriesId);
                        if ($seriesData && !empty($seriesData['Instances'])) {
                            $seriesData['_firstInstance'] = reset($seriesData['Instances']);
                            $study['SeriesData'][] = $seriesData;
                        }
                    }
                    $PACSStudy = $study;
                    $pdf->generateImagePages($PACSStudy, $PACS);
                }
            }
        }

        $filename = 'Hasil_Radiologi_' . str_replace(['/', '\\'], '_', $no_rawat) . '.pdf';

        return response($pdf->Output($filename, 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
