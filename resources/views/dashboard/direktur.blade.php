@extends('layouts.app')
@section('title', 'Dashboard Direktur')
@section('page-title', 'Executive Analytics')

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-slate-800 uppercase" style="letter-spacing: 1px;">RADIOLOGY PERFORMANCE INDEX</h5>
            <p class="text-muted small mb-0">Monthly statistics for management overview - <span
                    class="fw-bold text-emerald">{{ date('F Y') }}</span></p>
        </div>
        <div class="text-end">
            <div class="medizen-indicator active fw-bold" style="font-size: 11px;">
                <i data-feather="calendar" class="me-1" style="width: 12px;"></i>
                REPORTING PERIOD: {{ date('M Y') }}
            </div>
        </div>
    </div>

    <!-- Executive Stat Cards -->
    <div class="row g-2 mb-4">
        <div class="col-md-6 text-center">
            <div class="medizen-card-minimal p-4 bg-white">
                <div class="medizen-label-minimal mb-1">TOTAL DEMAND (MTD)</div>
                <div class="h2 mb-0 fw-bold text-emerald">{{ number_format($totalOrdersMonth) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 10px; letter-spacing: 1px;">Service Requests
                    Month-To-Date</div>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="medizen-card-minimal p-4 bg-white">
                <div class="medizen-label-minimal mb-1">FULFILLMENT RATE (MTD)</div>
                <div class="h2 mb-0 fw-bold text-primary">{{ number_format($totalCompletedMonth) }}</div>
                <div class="small text-muted mt-1 uppercase" style="font-size: 10px; letter-spacing: 1px;">Completed &
                    Validated Cases</div>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-lg-5">
            <div class="medizen-card-minimal h-100 bg-white">
                <div class="p-3 border-bottom d-flex align-items-center">
                    <i data-feather="bar-chart-2" class="me-2 text-emerald" style="width:14px;"></i>
                    <span class="medizen-label-minimal mb-0">MODALITY UTILIZATION</span>
                </div>
                <div class="p-4" style="min-height: 350px;">
                    <canvas id="modalityChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="medizen-card-minimal h-100 bg-white">
                <div class="p-3 border-bottom d-flex align-items-center">
                    <i data-feather="line-chart" class="me-2 text-emerald" style="width:14px;"></i>
                    <span class="medizen-label-minimal mb-0">MONTHLY VOLUME TREND ({{ now()->year }})</span>
                </div>
                <div class="p-4" style="min-height: 350px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const modalityData = @json($ordersByModality);
        new Chart(document.getElementById('modalityChart'), {
            type: 'bar',
            data: {
                labels: modalityData.map(m => m.modality.toUpperCase()),
                datasets: [{
                    label: 'Patients',
                    data: modalityData.map(m => m.total),
                    backgroundColor: '#10b981',
                    hoverBackgroundColor: '#059669',
                    borderRadius: 0,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        cornerRadius: 0,
                        titleFont: { size: 11, weight: '700' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10, family: 'Inter', weight: '700' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, family: 'Inter', weight: '700' } }
                    }
                }
            }
        });

        const monthlyData = @json($monthlyOrders);
        const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        const fullLabels = Array.from({ length: 12 }, (_, i) => months[i]);
        const dataMap = monthlyData.reduce((acc, current) => {
            acc[current.month - 1] = current.total;
            return acc;
        }, Array(12).fill(0));

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: fullLabels,
                datasets: [{
                    label: 'Volume Order',
                    data: dataMap,
                    borderColor: '#1e293b',
                    backgroundColor: 'rgba(30, 41, 59, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        cornerRadius: 0,
                        titleFont: { size: 11, weight: '700' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10, family: 'Inter', weight: '700' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, family: 'Inter', weight: '700' } }
                    }
                }
            }
        });
    </script>
@endpush