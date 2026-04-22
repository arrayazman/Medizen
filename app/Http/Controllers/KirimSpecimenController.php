<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatuSehatRadiologiService;

class KirimSpecimenController extends Controller
{
    private $ssService;

    public function __construct(SatuSehatRadiologiService $ssService)
    {
        $this->ssService = $ssService;
    }

    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_radiologi', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('permintaan_pemeriksaan_radiologi', 'permintaan_pemeriksaan_radiologi.noorder', '=', 'permintaan_radiologi.noorder')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw')
            ->join('satu_sehat_mapping_radiologi', 'satu_sehat_mapping_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
            ->join('satu_sehat_servicerequest_radiologi', function($join) {
                $join->on('satu_sehat_servicerequest_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                     ->on('satu_sehat_servicerequest_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->leftJoin('satu_sehat_specimen_radiologi', function($join) {
                $join->on('satu_sehat_specimen_radiologi.noorder', '=', 'permintaan_pemeriksaan_radiologi.noorder')
                     ->on('satu_sehat_specimen_radiologi.kd_jenis_prw', '=', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw');
            })
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                DB::raw('IFNULL(pasien.no_ktp,"") AS no_ktp_pasien'),
                'satu_sehat_encounter.id_encounter',
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.tgl_sampel',
                'permintaan_radiologi.jam_sampel',
                'jns_perawatan_radiologi.nm_perawatan',
                'satu_sehat_mapping_radiologi.sampel_code as code',
                'satu_sehat_mapping_radiologi.sampel_system as system',
                'satu_sehat_mapping_radiologi.sampel_display as display',
                'satu_sehat_servicerequest_radiologi.id_servicerequest',
                'satu_sehat_specimen_radiologi.id_specimen',
                'permintaan_pemeriksaan_radiologi.kd_jenis_prw'
            )
            ->whereBetween('permintaan_radiologi.tgl_permintaan', [$tgl1, $tgl2])
            ->where('permintaan_radiologi.tgl_sampel', '!=', '0000-00-00');

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%")
                  ->orWhere('permintaan_radiologi.noorder', 'like', "%$keyword%");
            });
        }

        $orders = $query->orderBy('permintaan_radiologi.noorder', 'desc')
                        ->paginate(25)
                        ->withQueryString();

        return view('satusehat.kirim_specimen', compact('orders', 'tgl1', 'tgl2', 'keyword'));
    }

    public function post(Request $request)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            if (empty($data['id_servicerequest'])) throw new \Exception('ServiceRequest ID belum ada atau belum dikirim.');

            $addLog('info', 'MEMINTA TOKEN & ID FHIR DARI SATUSEHAT...');
            $idPasien = $this->ssService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. Periksa NIK KTP Pasien: ' . $data['no_ktp_pasien']);

            $orgId = $this->ssService->getOrganizationId();
            $receivedTime = \Carbon\Carbon::parse($data['tgl_sampel'] . ' ' . $data['jam_sampel'])->toIso8601String();
            
            $payload = [
                'resourceType' => 'Specimen',
                'status' => 'available',
                'type' => [
                    'coding' => [
                        [
                            'system' => $data['system'] ?: 'http://snomed.info/sct',
                            'code' => $data['code'],
                            'display' => $data['display']
                        ]
                    ]
                ],
                'subject' => [
                    'reference' => "Patient/{$idPasien}",
                    'display' => $data['nm_pasien']
                ],
                'request' => [
                    [
                        'reference' => "ServiceRequest/{$data['id_servicerequest']}"
                    ]
                ],
                'receivedTime' => $receivedTime
            ];

            if ($data['id_specimen']) {
                $payload['id'] = $data['id_specimen'];
            } else {
                $payload['identifier'] = [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/specimen/{$orgId}",
                        'value' => "{$data['noorder']}.{$data['kd_jenis_prw']}"
                    ]
                ];
            }

            $isUpdate = !empty($data['id_specimen']);
            
            $addLog('info', ($isUpdate ? 'UPDATE' : 'KIRIM') . ' RESOURCE SPECIMEN...');
            $res = $this->ssService->sendResource('Specimen', $payload, $data['id_specimen'] ?: null);
            
            $fhirId = $res['id'];
            $addLog('ok', "BERHASIL: " . $fhirId);

            // Save to SIMRS
            $this->saveToSimrs($data['noorder'], $data['kd_jenis_prw'], $fhirId);

            return response()->json(['ok' => true, 'id_specimen' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }

    private function saveToSimrs($noorder, $kdJenisPrw, $fhirId)
    {
        DB::connection('simrs')->table('satu_sehat_specimen_radiologi')->updateOrInsert(
            ['noorder' => $noorder, 'kd_jenis_prw' => $kdJenisPrw],
            ['id_specimen' => $fhirId]
        );
    }
}
