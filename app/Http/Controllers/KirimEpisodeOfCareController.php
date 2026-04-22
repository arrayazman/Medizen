<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KirimEpisodeOfCareController extends Controller
{
    public function index(Request $request)
    {
        $tgl1 = $request->get('tgl1', date('Y-m-d'));
        $tgl2 = $request->get('tgl2', date('Y-m-d'));
        $keyword = $request->get('keyword');

        $query = DB::connection('simrs')->table('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->join('pegawai', 'pegawai.nik', '=', 'reg_periksa.kd_dokter')
            ->join('diagnosa_pasien', 'diagnosa_pasien.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('satu_sehat_mapping_diagnosa_episode', 'satu_sehat_mapping_diagnosa_episode.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit')
            ->join('satu_sehat_ref_episodeofcare_type', 'satu_sehat_ref_episodeofcare_type.kode', '=', 'satu_sehat_mapping_diagnosa_episode.kode_episode')
            ->leftJoin('satu_sehat_condition', function($join) {
                $join->on('satu_sehat_condition.no_rawat', '=', 'diagnosa_pasien.no_rawat')
                     ->on('satu_sehat_condition.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit');
            })
            ->leftJoin('satu_sehat_encounter', 'satu_sehat_encounter.no_rawat', '=', 'reg_periksa.no_rawat')
            ->leftJoin('satu_sehat_episodeofcare', function($join) {
                $join->on('satu_sehat_episodeofcare.no_rawat', '=', 'reg_periksa.no_rawat')
                     ->on('satu_sehat_episodeofcare.tipe', '=', 'satu_sehat_mapping_diagnosa_episode.kode_episode');
            })
            ->select(
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'pasien.no_ktp as no_ktp_pasien',
                'pegawai.no_ktp as no_ktp_dokter',
                'pegawai.nama as nm_dokter',
                'satu_sehat_mapping_diagnosa_episode.kode_episode',
                'satu_sehat_ref_episodeofcare_type.system_url',
                'satu_sehat_ref_episodeofcare_type.display as display_episode',
                'satu_sehat_condition.id_condition',
                'satu_sehat_encounter.id_encounter',
                'satu_sehat_episodeofcare.id_episodeofcare',
                'reg_periksa.stts'
            )
            ->whereBetween('reg_periksa.tgl_registrasi', [$tgl1, $tgl2]);

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('reg_periksa.no_rawat', 'like', "%$keyword%")
                  ->orWhere('reg_periksa.no_rkm_medis', 'like', "%$keyword%")
                  ->orWhere('pasien.nm_pasien', 'like', "%$keyword%");
            });
        }

        $perPage = $request->get('per_page', 25);
        if ($perPage == 'all') {
            $perPage = 1000000;
        }

        $orders = $query->orderBy('reg_periksa.tgl_registrasi', 'desc')
                        ->orderBy('satu_sehat_mapping_diagnosa_episode.kode_episode', 'asc')
                        ->paginate($perPage)->withQueryString();

        return view('satusehat.kirim_episodeofcare', compact('orders', 'tgl1', 'tgl2', 'keyword', 'perPage'));
    }

    public function post(Request $request, \App\Services\SatuSehatRadiologiService $ssService)
    {
        $logs = [];
        $addLog = function ($type, $msg) use (&$logs) { $logs[] = ['type' => $type, 'msg' => $msg]; };

        try {
            $data = $request->all();
            
            $addLog('info', 'MENGAMBIL ID FHIR PASIEN & DOKTER...');
            $idPasien = $ssService->getPatientId($data['no_ktp_pasien']);
            if (!$idPasien) throw new \Exception('Patient ID tidak ditemukan. NIK: ' . $data['no_ktp_pasien']);
            
            $idDokter = $ssService->getPractitionerId($data['no_ktp_dokter']);
            if (!$idDokter) throw new \Exception('Practitioner ID tidak ditemukan. NIK: ' . $data['no_ktp_dokter']);

            $orgId = $ssService->getOrganizationId();
            $periodStart = Carbon::parse($data['tgl_registrasi'] . ' ' . $data['jam_reg'])->toIso8601String();

            // Status mapping from Java reference
            $sttsReg = $data['stts'];
            $statusEpisode = "active";
            if($sttsReg == "Meninggal") $statusEpisode = "finished";
            elseif(in_array($sttsReg, ["Batal", "Pulang Paksa"])) $statusEpisode = "cancelled";
            elseif($sttsReg == "Dirujuk") $statusEpisode = "onhold";

            $payload = [
                'resourceType' => 'EpisodeOfCare',
                'status' => $statusEpisode,
                'statusHistory' => [
                    [
                        'status' => $statusEpisode,
                        'period' => [
                            'start' => $periodStart
                        ]
                    ]
                ],
                'type' => [
                    [
                        'coding' => [
                            [
                                'system' => $data['system_url'],
                                'code' => $data['kode_episode'],
                                'display' => $data['display_episode']
                            ]
                        ]
                    ]
                ],
                'patient' => [
                    'reference' => "Patient/{$idPasien}",
                    'display' => $data['nm_pasien']
                ],
                'managingOrganization' => [
                    'reference' => "Organization/{$orgId}"
                ],
                'period' => [
                    'start' => $periodStart
                ],
                'careManager' => [
                    'reference' => "Practitioner/{$idDokter}",
                    'display' => $data['nm_dokter']
                ]
            ];

            // Add Diagnosis if available
            if (!empty($data['id_condition'])) {
                $payload['diagnosis'] = [
                    [
                        'condition' => [
                            'reference' => "Condition/{$data['id_condition']}"
                        ],
                        'role' => [
                            'coding' => [
                                [
                                    'system' => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                    'code' => "AD",
                                    'display' => "Admission diagnosis"
                                ]
                            ]
                        ],
                        'rank' => 1
                    ]
                ];
            }

            if ($data['id_episodeofcare']) {
                $payload['id'] = $data['id_episodeofcare'];
            } else {
                $payload['identifier'] = [
                    [
                        'system' => "http://sys-ids.kemkes.go.id/episode-of-care/{$orgId}",
                        'value' => "{$data['no_rawat']}-{$data['kode_episode']}"
                    ]
                ];
            }

            $isUpdate = !empty($data['id_episodeofcare']);
            $addLog('info', ($isUpdate ? 'UPDATE' : 'KIRIM') . ' RESOURCE EPISODE OF CARE...');

            $res = $ssService->sendResource('EpisodeOfCare', $payload, $data['id_episodeofcare'] ?: null);
            
            $fhirId = $res['id'];
            $addLog('ok', "BERHASIL DISIMPAN: " . $fhirId);

            DB::connection('simrs')->table('satu_sehat_episodeofcare')->updateOrInsert(
                [
                    'no_rawat' => $data['no_rawat'],
                    'tipe' => $data['kode_episode']
                ],
                [
                    'tgl_mulai' => Carbon::parse($data['tgl_registrasi'])->format('Y-m-d'),
                    'id_episodeofcare' => $fhirId
                ]
            );

            return response()->json(['ok' => true, 'id_episodeofcare' => $fhirId, 'logs' => $logs]);

        } catch (\Exception $e) {
            $addLog('err', $e->getMessage());
            return response()->json(['ok' => false, 'msg' => $e->getMessage(), 'logs' => $logs]);
        }
    }
}
