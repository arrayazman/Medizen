@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Overview Operasional')

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-slate-800 uppercase" style="letter-spacing: 1px;">Medical System Overview</h5>
            <p class="text-muted small mb-0">Selamat datang, <span
                    class="fw-bold text-emerald">{{ Auth::user()->name }}</span>. Laporan aktivitas unit radiologi hari ini.
            </p>
        </div>
        <div class="text-end d-none d-md-block">
            <div class="medizen-indicator active fw-bold" style="font-size: 11px;">
                <i data-feather="calendar" class="me-1" style="width: 12px;"></i>
                {{ date('D, d F Y') }}
            </div>
        </div>
    </div>



    <!-- Stat Cards -->
    <div class="row g-2 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="medizen-card-minimal p-3 bg-white position-relative overflow-hidden">
                <div class="medizen-label-minimal mb-1">ORDERS TODAY</div>
                <div class="h3 mb-0 fw-bold text-emerald">{{ number_format($totalOrderToday) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Live scheduling
                    data</div>
                <i data-feather="clipboard" class="position-absolute text-emerald opacity-10"
                    style="right: 15px; bottom: 15px; width: 40px; height: 40px;"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="medizen-card-minimal p-3 bg-white position-relative overflow-hidden">
                <div class="medizen-label-minimal mb-1">COMPLETED & VALID</div>
                <div class="h3 mb-0 fw-bold text-primary">{{ number_format($totalCompleted) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Successfully
                    processed</div>
                <i data-feather="check-circle" class="position-absolute text-primary opacity-10"
                    style="right: 15px; bottom: 15px; width: 40px; height: 40px;"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="medizen-card-minimal p-3 bg-white position-relative overflow-hidden">
                <div class="medizen-label-minimal mb-1">IN QUEUE / PENDING</div>
                <div class="h3 mb-0 fw-bold text-warning">{{ number_format($totalPending) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Waiting for
                    action</div>
                <i data-feather="clock" class="position-absolute text-warning opacity-10"
                    style="right: 15px; bottom: 15px; width: 40px; height: 40px;"></i>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="medizen-card-minimal p-3 bg-white position-relative overflow-hidden">
                <div class="medizen-label-minimal mb-1">TOTAL PATIENT DB</div>
                <div class="h3 mb-0 fw-bold text-slate-700">{{ number_format($totalPatients) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Registered
                    records</div>
                <i data-feather="users" class="position-absolute text-slate-700 opacity-10"
                    style="right: 15px; bottom: 15px; width: 40px; height: 40px;"></i>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <!-- Orders by Modality -->
        <div class="col-lg-4">
            <div class="medizen-card-minimal h-100 bg-white">
                <div class="p-3 border-bottom d-flex align-items-center">
                    <i data-feather="pie-chart" class="me-2 text-emerald" style="width:14px;"></i>
                    <span class="medizen-label-minimal mb-0">MODALITY DISTRIBUTION</span>
                </div>
                <div class="p-4 d-flex align-items-center justify-content-center" style="min-height: 280px;">
                    <canvas id="modalityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="medizen-card-minimal h-100 bg-white">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i data-feather="activity" class="me-2 text-emerald" style="width:14px;"></i>
                        <span class="medizen-label-minimal mb-0">RECENT RADIOLOGY ORDERS</span>
                    </div>
                    <a href="{{ route('orders.index') }}" class="btn btn-emerald-soft medizen-btn-minimal py-1 px-2"
                        style="font-size: 9px;">
                        EXPLORE FULL LIST <i data-feather="arrow-right" class="ms-1" style="width: 10px;"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover medizen-table-minimal mb-0">
                        <thead>
                            <tr>
                                <th width="15%">REF NO.</th>
                                <th width="30%">PATIENT PROFILE</th>
                                <th width="15%" class="text-center">MODALITY</th>
                                <th width="20%">SCHEDULED</th>
                                <th width="20%" class="text-end">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td class="font-monospace fw-bold text-slate-700">{{ $order->order_number }}</td>
                                    <td>
                                        <div class="fw-bold text-slate-800">{{ strtoupper($order->patient->nama ?? '-') }}</div>
                                        <div class="text-muted uppercase" style="font-size: 9px; letter-spacing: 0.5px;">NORM:
                                            {{ $order->patient->no_rm ?? '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="medizen-indicator active px-2 py-1 bg-light border fw-bold"
                                            style="font-size: 10px;">
                                            {{ $order->modality }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-slate-700 fw-bold" style="font-size: 11px;">
                                            {{ $order->scheduled_date->format('d/m/Y') }}</div>
                                        <div class="text-muted" style="font-size: 10px;">{{ $order->scheduled_time ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span
                                            class="medizen-indicator {{ $order->status == 'COMPLETED' || $order->status == 'VALIDATED' ? 'active' : '' }} fw-bold">
                                            {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5 uppercase small letter-spacing-1">NO
                                        RECENT ACTIVITY DETECTED</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const modalityData = @json($ordersByModality);
        if (modalityData.length > 0) {
            new Chart(document.getElementById('modalityChart'), {
                type: 'doughnut',
                data: {
                    labels: modalityData.map(m => m.modality),
                    datasets: [{
                        data: modalityData.map(m => m.total),
                        backgroundColor: ['#1e293b', '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444'],
                        hoverOffset: 0,
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                boxWidth: 8,
                                font: {
                                    size: 9,
                                    weight: '700',
                                    family: 'Inter'
                                },
                                generateLabels: (chart) => {
                                    const data = chart.data;
                                    return data.labels.map((label, i) => ({
                                        text: label.toUpperCase(),
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: 'transparent',
                                        pointStyle: 'rect',
                                        hidden: false,
                                        index: i
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 11, weight: '700' },
                            bodyFont: { size: 11 },
                            padding: 12,
                            cornerRadius: 0,
                            displayColors: false
                        }
                    }
                }
            });
        }
    </script>
@endpush