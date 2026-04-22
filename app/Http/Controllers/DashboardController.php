<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use App\Models\Patient;
use App\Models\RadiologyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'super_admin', 'admin_radiologi', 'it_support' => $this->adminDashboard(),
            'dokter_radiologi' => $this->dokterDashboard(),
            'direktur' => $this->direkturDashboard(),
            'radiografer' => $this->radiograferDashboard(),
            default => $this->adminDashboard(),
        };
    }

    protected function adminDashboard()
    {
        $today = Carbon::today();

        $data = [
            'totalOrderToday' => RadiologyOrder::whereDate('scheduled_date', $today)->count(),
            'totalCompleted' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->whereIn('status', ['COMPLETED', 'REPORTED', 'VALIDATED'])->count(),
            'totalPending' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->whereIn('status', ['ORDERED', 'SENT_TO_PACS'])->count(),
            'totalInProgress' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->where('status', 'IN_PROGRESS')->count(),
            'totalPatients' => Patient::count(),
            'recentOrders' => RadiologyOrder::with(['patient', 'referringDoctor'])
                ->latest()->take(10)->get(),
            'ordersByModality' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->selectRaw('modality, count(*) as total')
                ->groupBy('modality')->get(),
        ];

        return view('dashboard.admin', $data);
    }

    protected function dokterDashboard()
    {
        $tab = request('tab', 'waiting');

        $pendingQuery = RadiologyOrder::with(['patient', 'examinationType'])
            ->whereIn('status', ['COMPLETED', 'REPORTED'])
            ->whereDoesntHave('result', function ($q) {
                $q->where('status', 'FINAL');
            });

        $completedQuery = RadiologyOrder::with(['patient', 'examinationType', 'result'])
            ->whereHas('result', function ($q) {
                $q->where('doctor_id', auth()->user()->doctor->id ?? null)
                    ->where('status', 'FINAL');
            })
            ->whereMonth('scheduled_date', now()->month);

        $data = [
            'pendingCount' => $pendingQuery->count(),
            'monthlyStats' => \App\Models\RadiologyResult::where('doctor_id', auth()->user()->doctor->id ?? null)
                ->where('status', 'FINAL')
                ->whereHas('order', function ($q) {
                    $q->whereMonth('scheduled_date', now()->month);
                })
                ->count(),
            'displayOrders' => ($tab === 'completed' ? $completedQuery : $pendingQuery)->latest()->get(),
            'tab' => $tab
        ];

        return view('dashboard.dokter', $data);
    }

    protected function direkturDashboard()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $data = [
            'ordersByModality' => RadiologyOrder::whereMonth('scheduled_date', $currentMonth)
                ->whereYear('scheduled_date', $currentYear)
                ->selectRaw('modality, count(*) as total')
                ->groupBy('modality')->get(),
            'monthlyOrders' => RadiologyOrder::whereYear('scheduled_date', $currentYear)
                ->selectRaw('MONTH(scheduled_date) as month, count(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'totalOrdersMonth' => RadiologyOrder::whereMonth('scheduled_date', $currentMonth)
                ->whereYear('scheduled_date', $currentYear)->count(),
            'totalCompletedMonth' => RadiologyOrder::whereMonth('scheduled_date', $currentMonth)
                ->whereYear('scheduled_date', $currentYear)
                ->whereIn('status', ['COMPLETED', 'REPORTED', 'VALIDATED'])->count(),
        ];

        return view('dashboard.direktur', $data);
    }

    protected function radiograferDashboard()
    {
        $today = Carbon::today();

        $data = [
            'todayOrders' => RadiologyOrder::with(['patient', 'examinationType'])
                ->whereDate('scheduled_date', $today)
                ->whereIn('status', ['ORDERED', 'SENT_TO_PACS', 'IN_PROGRESS'])
                ->orderBy('scheduled_time')
                ->get(),
            'completedToday' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->whereIn('status', ['COMPLETED', 'REPORTED', 'VALIDATED'])->count(),
        ];

        return view('dashboard.radiografer', $data);
    }

    public function about()
    {
        $setting = \App\Models\InstitutionSetting::first();
        return view('settings.about', compact('setting'));
    }
}
