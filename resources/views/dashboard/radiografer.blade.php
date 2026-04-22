@extends('layouts.app')
@section('title', 'Dashboard Radiografer')
@section('page-title', 'Technologist Worklist')

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-slate-800 uppercase" style="letter-spacing: 1px;">DAILY MODALITY OPERATIONS</h5>
            <p class="text-muted small mb-0">Selamat datang, <span
                    class="fw-bold text-emerald">{{ Auth::user()->name }}</span>. Kendalikan alur pemeriksaan hari ini.</p>
        </div>
        <div class="text-end">
            <div class="medizen-indicator active fw-bold" style="font-size: 11px;">
                <i data-feather="clock" class="me-1" style="width: 12px;"></i>
                ACTIVE SESSION: {{ date('H:i') }}
            </div>
        </div>
    </div>

    <!-- Radiographer Stat Cards -->
    <div class="row g-2 mb-4">
        <div class="col-md-6 text-center">
            <div class="medizen-card-minimal p-4 bg-white">
                <div class="medizen-label-minimal mb-1">ACTIVE QUEUE TODAY</div>
                <div class="h2 mb-0 fw-bold text-warning">{{ number_format($todayOrders->count()) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 10px; letter-spacing: 1px;">Patients awaiting
                    examination</div>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="medizen-card-minimal p-4 bg-white">
                <div class="medizen-label-minimal mb-1">COMPLETED TODAY</div>
                <div class="h2 mb-0 fw-bold text-emerald">{{ number_format($completedToday) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 10px; letter-spacing: 1px;">Procedures
                    finalized successfully</div>
            </div>
        </div>
    </div>

    <div class="medizen-card-minimal bg-white">
        <div class="p-3 border-bottom d-flex align-items-center">
            <i data-feather="calendar" class="me-2 text-emerald" style="width:14px;"></i>
            <span class="medizen-label-minimal mb-0">SCHEDULED EXAMINATIONS (TODAY)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="10%">TIME</th>
                        <th width="15%">REF NO.</th>
                        <th width="25%">PATIENT PROFILE</th>
                        <th width="15%" class="text-center">MODALITY</th>
                        <th width="20%">PROCEDURE</th>
                        <th width="15%" class="text-end">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayOrders as $order)
                        <tr>
                            <td class="fw-bold text-emerald font-monospace">
                                {{ \Carbon\Carbon::parse($order->scheduled_time)->format('H:i') }}</td>
                            <td class="text-slate-600 fw-bold font-monospace">{{ $order->order_number }}</td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ strtoupper($order->patient->nama ?? '-') }}</div>
                                <div class="text-muted uppercase" style="font-size: 9px; letter-spacing: 0.5px;">RM:
                                    {{ $order->patient->no_rm ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="medizen-indicator active px-2 mt-1 bg-light border fw-bold d-inline-block"
                                    style="font-size: 9px;">
                                    {{ $order->modality }}
                                </span>
                            </td>
                            <td>
                                <div class="text-slate-700 uppercase fw-bold" style="font-size: 11px;">
                                    {{ $order->examinationType->name ?? '-' }}</div>
                            </td>
                            <td class="text-end">
                                <span class="medizen-indicator {{ $order->status == 'IN_PROGRESS' ? 'active' : '' }} fw-bold">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5 uppercase small letter-spacing-1">
                                <div class="mb-2"><i data-feather="inbox" style="width: 24px; opacity: 0.3;"></i></div>
                                NO SCHEDULED PROCEDURES FOR TODAY
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection