<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RadiologyOrder; // Added this use statement for clarity

class RadiologyResultController extends Controller
{
    public function index(Request $request)
    {
        // Persist filters in session if they are present in the request
        if ($request->hasAny(['start_date', 'end_date', 'modality'])) {
            session(['results_filters' => $request->only(['start_date', 'end_date', 'modality'])]);
        } elseif ($request->has('clear_filters')) {
            session()->forget('results_filters');
            return redirect()->route('results.index', ['tab' => $request->tab ?? 'waiting']);
        }

        // Retrieve filters from session, with request parameters taking precedence
        $filters = session('results_filters', []);
        $tab = $request->input('tab', 'waiting'); // Keep original tab logic

        $startDate = $request->input('start_date', $filters['start_date'] ?? null);
        $endDate = $request->input('end_date', $filters['end_date'] ?? null);
        $modality = $request->input('modality', $filters['modality'] ?? null);
        $search = $request->input('search'); // Search is not session-persisted as per instruction snippet

        // Merge session-derived filters back into the request for consistent input() and filled() checks
        // and for passing to the view
        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'modality' => $modality,
            'search' => $search, // Ensure search is also merged if it was in the request
            'tab' => $tab, // Ensure tab is also merged
        ]);

        $query = RadiologyOrder::with(['patient', 'examinationType', 'result.doctor', 'referringDoctor'])
            ->whereIn('status', ['IN_PROGRESS', 'COMPLETED', 'REPORTED', 'VALIDATED'])
            ->orderBy('created_at', 'desc');

        // Apply default dates if no date filters (from request or session) and no search are present
        if (!$request->filled('start_date') && !$request->filled('end_date') && !$request->filled('search')) {
            $request->merge([
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString()
            ]);
            $startDate = $request->start_date; // Update local variable
            $endDate = $request->end_date;     // Update local variable
        }

        if ($request->filled('start_date')) {
            $query->whereDate('scheduled_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('scheduled_date', '<=', $request->end_date);
        }

        if ($tab === 'waiting') {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhereIn('status', ['IN_PROGRESS', 'COMPLETED', 'REPORTED']);
            })->whereDoesntHave('result', function ($q) {
                $q->where('status', 'FINAL');
            });
        } elseif ($tab === 'completed') {
            $query->whereHas('result', function ($q) {
                $q->where('status', 'FINAL');
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhere('accession_number', 'like', "%{$s}%")
                    ->orWhereHas('patient', function ($pq) use ($s) {
                        $pq->where('nama', 'like', "%{$s}%")
                            ->orWhere('no_rm', 'like', "%{$s}%");
                    });
            });
        }

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        $orders = $query->paginate(15);
        $modalities = \App\Models\Modality::active()->orderBy('code')->get();

        return view('results.index', compact('orders', 'tab', 'modalities'));
    }

    public function edit(\App\Models\RadiologyOrder $order, \App\Services\PACSClient $PACS)
    {
        $order->load(['patient', 'examinationType', 'result']);

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

        return view('results.edit', compact('order', 'PACSStudy', 'baseUrl'));
    }

    public function update(Request $request, \App\Models\RadiologyOrder $order)
    {
        $request->validate([
            'expertise' => 'required|string',
            'status' => 'required|in:DRAFT,FINAL'
        ]);

        $old = $order->toArray();

        \App\Models\RadiologyResult::updateOrCreate(
            ['radiology_order_id' => $order->id],
            [
                'expertise' => $request->expertise,
                'waktu_hasil' => $request->status === 'FINAL' ? now() : ($order->result->waktu_hasil ?? null),
                'status' => $request->status,
                'doctor_id' => auth()->user()->doctor_id ?? null
            ]
        );

        if ($request->status === 'FINAL') {
            $updateData = [];
            if (in_array($order->status, ['IN_PROGRESS', 'COMPLETED'])) {
                $updateData['status'] = \App\Models\RadiologyOrder::STATUS_REPORTED;
            }
            if (empty($order->patient_portal_token)) {
                // Generate a unique robust token
                $updateData['patient_portal_token'] = \Illuminate\Support\Str::random(10) . '-' . uniqid();
            }
            if (!empty($updateData)) {
                $order->update($updateData);
            }
        }

        \App\Services\AuditService::logUpdate($order, $old, 'Expertise diperbarui via module Hasil Pemeriksaan');

        return redirect()->route('results.index')->with('success', 'Hasil pemeriksaan berhasil disimpan.');
    }
}

