@extends('layouts.app')

@section('title', 'Antrean Sampling')
@section('page-title', 'Antrean Sampling')

@section('content')
    <style>
        /* Styling khusus antrean premium */
        .queue-card {
            border: none;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }

        .queue-card:hover {
            transform: translateY(-2px);
        }

        .status-badge-sampling {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .status-waiting {
            background-color: #fff9db;
            color: #f08c00;
            border-color: #ffe066;
        }

        .status-ordered {
            background-color: #e9ecef;
            color: #495057;
            border-color: #dee2e6;
        }

        .btn-action-sampling {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.75rem;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary-action {
            background: #10b981;
            color: white;
        }

        .btn-primary-action:hover {
            background: #059669;
            color: white;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }

        .patient-id-badge {
            background: #f8fafc;
            color: #64748b;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .queue-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a202c;
        }

        .table-queue thead th {
            background-color: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 15px;
            border-bottom: 2px solid #edf2f7;
        }

        .table-queue tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f7fafc;
        }

        /* Stats Bar */
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            text-align: center;
            flex: 1;
        }

        .stat-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
        }

        .stat-icon {
            width: 14px;
            height: 14px;
            margin-right: 5px;
            vertical-align: middle;
            opacity: 0.6;
        }

        /* Auto-pulse for latest item */
        @keyframes pulse {
            0% {
                background-color: #ffffff;
            }

            50% {
                background-color: #ecfdf5;
            }

            100% {
                background-color: #ffffff;
            }
        }

        .latest-queue {
            animation: pulse 2s infinite;
        }

        /* Fullscreen Mode (for TV/Dashboard) */
        body.display-mode .sidebar,
        body.display-mode .top-navbar {
            display: none !important;
        }

        body.display-mode .main-content {
            margin-left: 0 !important;
            padding-top: 0 !important;
        }

        body.display-mode .page-content {
            padding: 40px !important;
            background-color: #f8fafc !important;
        }
    </style>

    <div class="d-flex flex-column gap-4">
        <!-- Header Controls -->
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-slate-800 mb-1">SAMPLING PIPELINE</h5>
                <div class="text-muted small" style="font-size: 0.7rem; letter-spacing: 0.5px;">PATIENT QUEUE MANAGEMENT
                    SYSTEM</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('queue.display') }}" target="_blank" class="btn btn-white btn-sm border fw-bold">
                    <i data-feather="monitor" class="me-1" style="width:14px"></i> FULLSCREEN VIEW
                </a>
                <button class="btn btn-white btn-sm border fw-bold" onclick="window.location.reload()">
                    <i data-feather="refresh-cw" class="me-1" style="width:14px"></i> REFRESH
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="d-flex gap-3 flex-wrap">
            <div class="stat-box">
                <span class="stat-label">WAITING FOR SAMPLING</span>
                <div class="stat-value text-emerald">
                    <i data-feather="users" class="stat-icon"></i>{{ $stats['waiting'] }}
                </div>
            </div>
            <div class="stat-box">
                <span class="stat-label">COMPLETED SAMPLES TODAY</span>
                <div class="stat-value text-slate-700">
                    <i data-feather="check-circle" class="stat-icon"></i>{{ $stats['completed_today'] }}
                </div>
            </div>
            <div class="stat-box d-none d-md-block">
                <span class="stat-label">CURRENT TIME</span>
                <div class="stat-value text-slate-400" id="digitalClock">--:--:--</div>
            </div>
        </div>

        <!-- Queue Table -->
        <div class="card queue-card">
            <div class="table-responsive">
                <table class="table table-queue mb-0">
                    <thead>
                        <tr>
                            <th width="80" class="text-center">QUEUE #</th>
                            <th>PATIENT NAME</th>
                            <th>PROCEDURE / DESCRIPTION</th>
                            <th class="text-center">ROOM / STATION</th>
                            <th class="text-center">REQ TIME</th>
                            <th class="text-center">PRIORITY</th>
                            <th class="text-end">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($queue as $i => $order)
                            <tr class="{{ $i == 0 && count($queue) > 0 ? 'latest-queue' : '' }}">
                                <td class="text-center">
                                    <span class="queue-number">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ strtoupper($order->patient->nama) }}</div>
                                    <span class="patient-id-badge">{{ $order->patient->no_rm }}</span>
                                </td>
                                <td>
                                    <div class="text-slate-700 small fw-bold">
                                        {{ $order->examinationType->name ?? 'Standard Procedure' }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $order->modality }} ·
                                        {{ $order->accession_number }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-slate-600 border px-2 py-1" style="font-size: 0.65rem;">
                                        <i data-feather="map-pin"
                                            style="width:10px; margin-right:4px"></i>{{ $order->room->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center text-slate-600 fw-bold" style="font-size: 0.8rem;">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}
                                </td>
                                <td class="text-center">
                                    @if($order->priority == 'CITO' || $order->priority == 'STAT')
                                        <span class="badge bg-danger text-white px-2 py-1"
                                            style="font-size: 0.6rem;">{{ $order->priority }}</span>
                                    @else
                                        <span class="badge bg-emerald-soft text-emerald px-2 py-1"
                                            style="font-size: 0.6rem;">{{ $order->priority }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('orders.take-sample', $order) }}" method="POST"
                                        class="d-inline swal-confirm" data-swal-title="Prosedur Sampling"
                                        data-swal-text="Pastikan identitas pasien sesuai. Mulai proses sampling untuk {{ $order->patient->nama }}?">
                                        @csrf
                                        <button type="submit" class="btn btn-action-sampling btn-primary-action shadow-sm">
                                            TAKE SAMPLE
                                        </button>
                                    </form>
                                    <a href="{{ route('orders.show', $order) }}"
                                        class="btn btn-action-sampling btn-light border ms-1">
                                        DETAIL
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="opacity-20 mb-3">
                                        <i data-feather="coffee" style="width:64px; height:64px"></i>
                                    </div>
                                    <h5 class="fw-bold text-slate-400">PIPE IS EMPTY</h5>
                                    <p class="text-muted small">No patients waiting for sampling at the moment.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function updateClock() {
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const s = String(now.getSeconds()).padStart(2, '0');
                document.getElementById('digitalClock').textContent = `${h}:${m}:${s}`;
            }

            setInterval(updateClock, 1000);
            updateClock();

            function toggleDisplayMode() {
                document.body.classList.toggle('display-mode');
                // Persist state if needed
            }

            // Auto refresh every 30 seconds
            setTimeout(function () {
                window.location.reload();
            }, 30000);
        </script>
    @endpush
@endsection