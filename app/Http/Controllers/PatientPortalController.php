<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientPortalController extends Controller
{
    public function index()
    {
        if (session()->has('patient_id')) {
            return redirect()->route('portal.dashboard');
        }
        return view('portal.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'no_rm' => 'required',
            'nik' => 'required',
        ]);

        $patient = \App\Models\Patient::where('no_rm', $request->no_rm)
            ->where('nik', $request->nik)
            ->first();

        if (!$patient) {
            return redirect()->back()->with('error', 'Data tidak ditemukan. Pastikan No. RM dan NIK sesuai.');
        }

        session(['patient_id' => $patient->id]);

        return redirect()->route('portal.dashboard');
    }

    public function dashboard()
    {
        $patientId = session('patient_id');
        if (!$patientId)
            return redirect()->route('portal.index');

        $patient = \App\Models\Patient::findOrFail($patientId);
        $orders = \App\Models\RadiologyOrder::where('patient_id', $patientId)
            ->with(['examinationType', 'result'])
            ->latest()
            ->get();

        return view('portal.dashboard', compact('patient', 'orders'));
    }

    public function logout()
    {
        session()->forget('patient_id');
        return redirect()->route('portal.index');
    }

    public function show($token, \App\Services\PACSClient $PACS)
    {
        $order = \App\Models\RadiologyOrder::where('patient_portal_token', $token)->firstOrFail();

        // Security check, only show finalised orders
        if ($order->result?->status !== 'FINAL') {
            abort(404, 'Hasil pemeriksaan belum tersedia.');
        }

        $order->load(['patient', 'examinationType', 'result', 'report.dokter']);

        $PACSStudy = null;
        if ($order->accession_number) {
            $result = $PACS->post('/tools/find', [
                'Level' => 'Study',
                'Query' => ['AccessionNumber' => $order->accession_number],
                'Limit' => 1
            ]);

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
                    usort($study['SeriesData'], function ($a, $b) {
                        $numA = (int) ($a['MainDicomTags']['SeriesNumber'] ?? 0);
                        $numB = (int) ($b['MainDicomTags']['SeriesNumber'] ?? 0);
                        return $numA <=> $numB;
                    });
                    $PACSStudy = $study;
                }
            }
        }

        $baseUrl = $PACS->getPublicUrl();

        return view('portal.result', compact('order', 'PACSStudy', 'baseUrl'));
    }
}

