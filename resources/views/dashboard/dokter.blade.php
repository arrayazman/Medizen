@extends('layouts.app')
@section('title', 'Dashboard Dokter')
@section('page-title', 'Physician Portal')

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-slate-800 uppercase" style="letter-spacing: 1px;">DIAGNOSTIC WORKLIST</h5>
            <p class="text-muted small mb-0">Selamat datang, <span class="fw-bold text-emerald">dr.
                    {{ Auth::user()->name }}</span>. Berikut prioritas ekspertise Anda.</p>
        </div>
        <div class="text-end">
            <div class="medizen-indicator active fw-bold" style="font-size: 11px;">
                <i data-feather="user" class="me-1" style="width: 12px;"></i>
                STATUS: ON DUTY
            </div>
        </div>
    </div>

    <!-- Doctor Stat Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <a href="{{ route('dashboard', ['tab' => 'waiting']) }}" class="text-decoration-none d-block h-100">
                <div class="medizen-card-minimal p-3 rounded-0 h-100 {{ $tab == 'waiting' ? 'border-amber shadow-none border-2' : 'bg-white opacity-75 border' }}">
                    <div class="medizen-label-minimal mb-1 text-warning" style="font-size: 0.65rem;">WAITING</div>
                    <div class="h4 mb-0 fw-bold text-warning">{{ number_format($pendingCount) }}</div>
                    <div class="small text-muted mt-1 uppercase d-none d-md-block" style="font-size: 9px; letter-spacing: 0.5px;">PENDING EXPERTISE</div>
                </div>
            </a>
        </div>
        <div class="col-6">
            <a href="{{ route('dashboard', ['tab' => 'completed']) }}" class="text-decoration-none d-block h-100">
                <div class="medizen-card-minimal p-3 rounded-0 h-100 {{ $tab == 'completed' ? 'border-emerald shadow-none border-2' : 'bg-white opacity-75 border' }}">
                    <div class="medizen-label-minimal mb-1 text-emerald" style="font-size: 0.65rem;">TOTAL (MTD)</div>
                    <div class="h4 mb-0 fw-bold text-emerald">{{ number_format($monthlyStats) }}</div>
                    <div class="small text-muted mt-1 uppercase d-none d-md-block" style="font-size: 9px; letter-spacing: 0.5px;">VALIDATED THIS MONTH</div>
                </div>
            </a>
        </div>
    </div>

    <div class="medizen-card-minimal bg-white rounded-0 border">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i data-feather="{{ $tab == 'waiting' ? 'list' : 'check-circle' }}"
                    class="me-2 text-{{ $tab == 'waiting' ? 'warning' : 'emerald' }}" style="width:14px;"></i>
                <span class="medizen-label-minimal mb-0">
                    {{ $tab == 'waiting' ? 'PENDING EXPERTISE QUEUE' : 'MTD VALIDATED REPORTS' }}
                </span>
            </div>
            <a href="{{ route('results.index', ['tab' => $tab]) }}"
                class="btn btn-link btn-sm text-decoration-none p-0 text-muted small uppercase fw-bold"
                style="font-size: 10px;">
                VIEW ALL <i data-feather="arrow-right" style="width:10px;"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="15%" class="small py-2">REF NO.</th>
                        <th width="35%" class="small py-2">PATIENT</th>
                        <th width="35%" class="small py-2">EXAM</th>
                        <th width="15%" class="text-end small py-2">DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displayOrders as $order)
                        <tr onclick="window.location='{{ route('results.edit', $order) }}'" style="cursor: pointer;">
                            <td class="py-2">
                                <div class="font-monospace fw-bold text-slate-700 small">{{ $order->order_number }}</div>
                            </td>
                            <td class="py-2">
                                <div class="fw-bold text-slate-800 small text-truncate" style="max-width: 120px;">{{ strtoupper($order->patient->nama ?? '-') }}</div>
                                <div class="text-muted uppercase" style="font-size: 8px;">RM: {{ $order->patient->no_rm ?? '-' }}</div>
                            </td>
                            <td class="py-2">
                                <div class="fw-bold text-slate-700 small text-truncate" style="max-width: 120px;">{{ strtoupper($order->examinationType->name ?? '-') }}</div>
                                <div class="medizen-indicator px-1 bg-light border fw-bold d-inline-block" style="font-size: 8px;">
                                    {{ $order->modality }}
                                </div>
                            </td>
                            <td class="text-end py-2">
                                <div class="text-slate-700 fw-bold" style="font-size: 10px;">{{ $order->scheduled_date->format('d/m/y') }}</div>
                                <div class="text-muted" style="font-size: 9px;">{{ $order->scheduled_time ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5 uppercase small letter-spacing-1">
                                <div class="mb-2"><i data-feather="coffee" style="width: 24px; opacity: 0.3;"></i></div>
                                NO RECORDS FOUND
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection