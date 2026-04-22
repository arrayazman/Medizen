<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pasien - Portal Radiologi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        :root {
            --emerald: #10b981;
            --emerald-dark: #059669;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-100: #f1f5f9;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: var(--slate-900);
        }

        .navbar-custom {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 0;
        }

        .header-section {
            background: linear-gradient(135deg, var(--slate-900), var(--slate-800));
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .patient-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #e2e8f0;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .order-card:hover {
            transform: translateX(5px);
            border-left-color: var(--emerald);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .order-card.finalised {
            border-left-color: var(--emerald);
        }

        .status-badge {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .bg-emerald-soft { background: #ecfdf5; color: #059669; }
        .bg-slate-soft { background: #f8fafc; color: #64748b; }

        .btn-logout {
            color: #ef4444;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <nav class="navbar-navbar-expand navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div style="background: var(--emerald); color: white; padding: 6px; border-radius: 8px;">
                    <i data-feather="activity" style="width: 20px; height: 20px;"></i>
                </div>
                <span class="fw-800 fs-5" style="letter-spacing: -0.5px;">Portal Pasien</span>
            </div>
            <form action="{{ route('portal.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-logout bg-transparent border-0">
                    <i data-feather="log-out" style="width: 18px; height: 18px;"></i> Keluar
                </button>
            </form>
        </div>
    </nav>

    <div class="header-section">
        <div class="container">
            <h2 class="fw-800 mb-1">Selamat Datang, {{ $patient->nama }}</h2>
            <p class="opacity-75 mb-0">No. RM: <strong class="text-white">{{ $patient->no_rm }}</strong> | NIK: {{ $patient->nik }}</p>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <h5 class="fw-800 mb-0">Riwayat Pemeriksaan</h5>
                    <span class="text-muted small">{{ $orders->count() }} Pemeriksaan Ditemukan</span>
                </div>

                @forelse($orders as $order)
                    @php 
                        $isFinal = ($order->result && $order->result->status === 'FINAL') || in_array($order->status, ['REPORTED', 'VALIDATED', 'COMPLETED']);
                    @endphp
                    
                    @if($isFinal)
                        <a href="{{ route('portal.result', $order->patient_portal_token) }}" class="order-card finalised">
                    @else
                        <div class="order-card">
                    @endif
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="text-muted small fw-600 d-block">{{ \Carbon\Carbon::parse($order->scheduled_date)->format('d F Y') }}</span>
                                <h6 class="fw-800 mb-0 mt-1">{{ $order->examinationType->name }}</h6>
                            </div>
                            @if($isFinal)
                                <span class="status-badge bg-emerald-soft">HASIL TERSEDIA</span>
                            @else
                                <span class="status-badge bg-slate-soft">PROSES PERIKSA</span>
                            @endif
                        </div>
                        <div class="d-flex gap-3 align-items-center mt-3">
                            <div class="d-flex align-items-center gap-1 text-muted small">
                                <i data-feather="monitor" style="width: 12px;"></i> {{ $order->modality }}
                            </div>
                            <div class="d-flex align-items-center gap-1 text-muted small">
                                <i data-feather="hash" style="width: 12px;"></i> {{ $order->accession_number }}
                            </div>
                            @if($isFinal)
                                <div class="ms-auto text-emerald fw-bold small d-flex align-items-center gap-1">
                                    LIHAT HASIL <i data-feather="chevron-right" style="width: 14px;"></i>
                                </div>
                            @endif
                        </div>
                    @if($isFinal)
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <div class="patient-card empty-state">
                        <i data-feather="folder-minus" style="width: 48px; height: 48px; margin-bottom: 15px;"></i>
                        <h6 class="fw-bold">Belum Ada Riwayat</h6>
                        <p class="small mb-0">Pemeriksaan Anda belum tercatat atau masih dalam tahap pendaftaran.</p>
                    </div>
                @endforelse
            </div>

            <div class="col-lg-4">
                <div class="patient-card">
                    <h6 class="fw-800 mb-3 text-uppercase small text-muted">Bantuan & Dukungan</h6>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="background: var(--slate-100); padding: 10px; border-radius: 12px;">
                            <i data-feather="info" class="text-slate-800" style="width: 20px;"></i>
                        </div>
                        <div>
                            <p class="small mb-0"><strong>Hasil belum muncul?</strong> Laporan hasil rontgen biasanya memerlukan waktu 1x24 jam untuk divalidasi oleh dokter radiologi.</p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\InstitutionSetting::first()->phone ?? '08123456789') }}" class="btn btn-outline-dark btn-sm w-100 fw-bold py-2" style="border-radius: 10px;">
                        <i data-feather="message-circle" style="width: 14px; height: 14px;" class="me-1"></i> HUBUNGI RADIOLOGI
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
