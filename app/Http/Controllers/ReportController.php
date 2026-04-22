<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function durationReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $query = RadiologyOrder::with(['patient', 'referringDoctor', 'result'])
            ->whereHas('result', function ($q) {
                $q->whereNotNull('waktu_hasil');
            })
            ->whereBetween('scheduled_date', [$startDate, $endDate]);

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        $orders = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get();

        // Calculations
        $stats = [
            'avg_request_to_sample' => 0,
            'avg_sample_to_result' => 0,
            'avg_total' => 0,
            'count' => count($orders),
            'breakdown' => [
                'request_to_sample' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
                'sample_to_result' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
                'total' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
            ]
        ];

        $totalReqSample = 0;
        $totalSampleResult = 0;
        $totalOverall = 0;

        foreach ($orders as $order) {
            $reqTime = Carbon::parse($order->created_at);
            $sampleTime = $order->waktu_sample ? Carbon::parse($order->waktu_sample) : null;
            $resultTime = Carbon::parse($order->result->waktu_hasil);

            // 1. Request to Sample
            if ($sampleTime) {
                $diffReqSample = $reqTime->diffInMinutes($sampleTime);
                $order->duration_req_sample = $diffReqSample;
                $totalReqSample += $diffReqSample;
                $this->updateBreakdown($stats['breakdown']['request_to_sample'], $diffReqSample);
            }

            // 2. Sample to Result
            if ($sampleTime) {
                $diffSampleResult = $sampleTime->diffInMinutes($resultTime);
                $order->duration_sample_result = $diffSampleResult;
                $totalSampleResult += $diffSampleResult;
                $this->updateBreakdown($stats['breakdown']['sample_to_result'], $diffSampleResult);
            }

            // 3. Total (Request to Result)
            $diffTotal = $reqTime->diffInMinutes($resultTime);
            $order->duration_total = $diffTotal;
            $totalOverall += $diffTotal;
            $this->updateBreakdown($stats['breakdown']['total'], $diffTotal);
        }

        if ($stats['count'] > 0) {
            $stats['avg_request_to_sample'] = round($totalReqSample / $stats['count'], 2);
            $stats['avg_sample_to_result'] = round($totalSampleResult / $stats['count'], 2);
            $stats['avg_total'] = round($totalOverall / $stats['count'], 2);
        }

        $modalities = DB::table('radiology_orders')->select('modality')->distinct()->pluck('modality');

        return view('reports.duration', compact('orders', 'stats', 'startDate', 'endDate', 'modalities'));
    }

    public function examinationReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $query = RadiologyOrder::with(['examinationType.modality', 'result'])
            ->whereBetween('scheduled_date', [$startDate, $endDate]);

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        $orders = $query->get();

        // Group by examination type
        $reportData = $orders->groupBy('examination_type_id')->map(function ($group) {
            $first = $group->first();
            $examName = $first->examinationType->name ?? 'Unknown';
            $price = $first->examinationType->price ?? 0;
            $modality = $first->modality;

            $validDurations = $group->filter(fn($o) => !empty($o->result?->waktu_hasil))->map(function ($o) {
                return Carbon::parse($o->created_at)->diffInMinutes(Carbon::parse($o->result->waktu_hasil));
            });

            return [
                'name' => $examName,
                'modality' => $modality,
                'count' => $group->count(),
                'avg_duration' => $validDurations->count() > 0 ? round($validDurations->average(), 2) : 0,
                'total_revenue' => $group->count() * $price
            ];
        })->sortByDesc('count');

        $modalities = DB::table('radiology_orders')->select('modality')->distinct()->pluck('modality');

        return view('reports.examination', compact('reportData', 'startDate', 'endDate', 'modalities'));
    }

    public function durationByExamination(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $query = RadiologyOrder::with(['examinationType.modality', 'result'])
            ->whereHas('result', function ($q) {
                $q->whereNotNull('waktu_hasil');
            })
            ->whereBetween('scheduled_date', [$startDate, $endDate]);

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        $orders = $query->get();

        $reportData = $orders->groupBy('examination_type_id')->map(function ($group) {
            $first = $group->first();
            $examName = $first->examinationType->name ?? 'Unknown';
            $modality = $first->modality;

            $reqToSample = $group->filter(fn($o) => !empty($o->waktu_sample))->map(function ($o) {
                return Carbon::parse($o->created_at)->diffInMinutes(Carbon::parse($o->waktu_sample));
            });

            $sampleToResult = $group->filter(fn($o) => !empty($o->waktu_sample))->map(function ($o) {
                return Carbon::parse($o->waktu_sample)->diffInMinutes(Carbon::parse($o->result->waktu_hasil));
            });

            $total = $group->map(function ($o) {
                return Carbon::parse($o->created_at)->diffInMinutes(Carbon::parse($o->result->waktu_hasil));
            });

            return [
                'name' => $examName,
                'modality' => $modality,
                'count' => $group->count(),
                'avg_req_sample' => $reqToSample->count() > 0 ? round($reqToSample->average(), 2) : 0,
                'avg_sample_result' => $sampleToResult->count() > 0 ? round($sampleToResult->average(), 2) : 0,
                'avg_total' => $total->count() > 0 ? round($total->average(), 2) : 0,
            ];
        })->sortByDesc('count');

        $modalities = DB::table('radiology_orders')->select('modality')->distinct()->pluck('modality');

        return view('reports.duration_by_exam', compact('reportData', 'startDate', 'endDate', 'modalities'));
    }

    public function requestReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $query = RadiologyOrder::with(['patient', 'examinationType', 'referringDoctor'])
            ->whereBetween('scheduled_date', [$startDate, $endDate]);

        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('accession_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('nama', 'like', "%{$search}%")
                            ->orWhere('no_rm', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Calculate stats for the current page of orders
        $stats = [
            'avg_request_to_sample' => 0,
            'avg_sample_to_result' => 0,
            'avg_total' => 0,
            'count' => $orders->count(),
            'breakdown' => [
                'request_to_sample' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
                'sample_to_result' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
                'total' => ['0-15' => 0, '15-30' => 0, '30-60' => 0, '60-120' => 0, '>120' => 0],
            ]
        ];

        $totalReqSample = 0;
        $totalSampleResult = 0;
        $totalOverall = 0;
        $countReqSample = 0;
        $countSampleResult = 0;
        $countTotal = 0;

        foreach ($orders as $order) {
            if ($order->duration_req_sample !== null) {
                $totalReqSample += $order->duration_req_sample;
                $countReqSample++;
                $this->updateBreakdown($stats['breakdown']['request_to_sample'], $order->duration_req_sample);
            }
            if ($order->duration_sample_result !== null) {
                $totalSampleResult += $order->duration_sample_result;
                $countSampleResult++;
                $this->updateBreakdown($stats['breakdown']['sample_to_result'], $order->duration_sample_result);
            }
            if ($order->duration_total !== null) {
                $totalOverall += $order->duration_total;
                $countTotal++;
                $this->updateBreakdown($stats['breakdown']['total'], $order->duration_total);
            }
        }

        if ($countReqSample > 0)
            $stats['avg_request_to_sample'] = round($totalReqSample / $countReqSample);
        if ($countSampleResult > 0)
            $stats['avg_sample_to_result'] = round($totalSampleResult / $countSampleResult);
        if ($countTotal > 0)
            $stats['avg_total'] = round($totalOverall / $countTotal);

        $modalities = DB::table('radiology_orders')->select('modality')->distinct()->pluck('modality');
        $statuses = RadiologyOrder::statuses();

        return view('reports.requests', compact('orders', 'stats', 'startDate', 'endDate', 'modalities', 'statuses'));
    }

    private function updateBreakdown(&$group, $minutes)
    {
        if ($minutes <= 15)
            $group['0-15']++;
        elseif ($minutes <= 30)
            $group['15-30']++;
        elseif ($minutes <= 60)
            $group['30-60']++;
        elseif ($minutes <= 120)
            $group['60-120']++;
        else
            $group['>120']++;
    }
}
