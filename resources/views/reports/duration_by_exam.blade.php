@extends('layouts.app')
@section('title', 'Lama Pelayanan Per Jenis Pemeriksaan')
@section('page-title', 'Rata-rata Durasi')

@section('content')
    <style>
        .card-medizen {
            border: 1px solid rgba(0, 0, 0, 0.03);
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            background: #fff;
        }

        .card-medizen .card-header {
            background: #fff;
            border-bottom: 1px solid #f8fafc;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            padding: 0.8rem 1rem;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }

        .table-medizen {
            font-size: 0.7rem;
            margin-bottom: 0;
        }

        .table-medizen th {
            background: #fcfcfc;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.6rem;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            border-bottom: 2px solid #f1f5f9;
            white-space: nowrap;
        }

        .table-medizen td {
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f8fafc;
            color: #475569;
        }

        .table-medizen tr:hover td {
            background-color: #f8fafc;
        }

        .btn-medizen {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 2px;
            padding: 0.35rem 0.75rem;
            text-transform: uppercase;
            box-shadow: none !important;
        }

        .btn-primary-custom {
            background-color: #188754;
            color: white;
            border: 1px solid #188754;
        }

        .btn-primary-custom:hover {
            background-color: #147347;
            color: white;
            border-color: #147347;
        }

        .btn-outline-custom {
            background-color: transparent;
            color: #188754;
            border: 1px solid #188754;
        }

        .btn-outline-custom:hover {
            background-color: #188754;
            color: white;
        }

        .text-custom {
            color: #188754 !important;
        }

        .bg-custom-soft {
            background-color: rgba(24, 135, 84, 0.08) !important;
        }

        .form-control-medizen,
        .form-select-medizen {
            border-radius: 2px;
            font-size: 0.7rem;
            border-color: #e2e8f0;
            padding: 0.4rem 0.75rem;
            box-shadow: none !important;
        }

        .form-control-medizen:focus,
        .form-select-medizen:focus {
            border-color: #188754;
            background-color: #fff;
        }
    </style>

    <div class="row layout-row mb-3 align-items-center no-print">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-slate-800" style="font-size: 1.1rem; letter-spacing: -0.5px;">RATA-RATA DURASI
                    PEMERIKSAAN</h4>
                <p class="text-muted small mb-0" style="font-size: 0.7rem;">Pemantauan waktu layanan spesifik berdasarkan
                    jenis pemeriksaan dan modalitas.</p>
            </div>
            <button type="button" class="btn btn-medizen btn-outline-custom d-flex align-items-center"
                onclick="window.print()">
                <i data-feather="printer" style="width:14px;height:14px" class="me-1"></i> CETAK LAPORAN
            </button>
        </div>
    </div>

    <div class="card card-medizen mb-3 p-3 bg-light-soft border-0 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.duration-by-exam') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">TANGGAL
                    MULAI</label>
                <input type="date" name="start_date"
                    class="form-control form-control-medizen bg-white fw-bold text-slate-700" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">TANGGAL
                    SELESAI</label>
                <input type="date" name="end_date" class="form-control form-control-medizen bg-white fw-bold text-slate-700"
                    value="{{ $endDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold mb-1"
                    style="font-size: 0.6rem; letter-spacing: 0.5px;">MODALITAS</label>
                <select name="modality" class="form-select form-select-medizen bg-white fw-bold text-slate-700">
                    <option value="">SEMUA MODALITAS</option>
                    @foreach($modalities as $m)
                        <option value="{{ $m }}" {{ request('modality') == $m ? 'selected' : '' }}>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit"
                    class="btn btn-medizen btn-dark w-100 d-flex justify-content-center align-items-center rounded-2 py-2">
                    <i data-feather="filter" style="width:14px;height:14px" class="me-1"></i> TETAPKAN FILTER
                </button>
            </div>
        </form>
    </div>

    <div class="card card-medizen border-0 shadow-sm mb-3">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center pb-2 pt-3">
            <div class="d-flex align-items-center">
                <div class="p-1 rounded bg-custom-soft me-2 text-custom">
                    <i data-feather="list" style="width:14px; height: 14px;"></i>
                </div>
                RESUME DURASI PER PEMERIKSAAN
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr class="text-center align-middle">
                            <th rowspan="2" style="width: 50px;">NO</th>
                            <th rowspan="2" class="text-start ps-3">JENIS PEMERIKSAAN</th>
                            <th rowspan="2">MODALITAS</th>
                            <th rowspan="2">JML DATA</th>
                            <th colspan="3" class="border-bottom-0 pb-1">RATA-RATA DURASI (MENIT)</th>
                        </tr>
                        <tr class="text-center align-middle">
                            <th style="width: 150px; border-top: 1px solid #f1f5f9;">PERMINTAAN ➔ SAMPEL</th>
                            <th style="width: 150px; border-top: 1px solid #f1f5f9;">SAMPEL ➔ HASIL</th>
                            <th style="width: 150px; border-top: 1px solid #f1f5f9;">PERMINTAAN ➔ HASIL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $index => $item)
                            <tr>
                                <td class="text-center text-muted fw-bold">{{ $loop->iteration }}</td>
                                <td class="fw-bold text-slate-800 text-start ps-3" style="font-size: 0.75rem;">
                                    {{ $item['name'] }}</td>
                                <td class="text-center">
                                    <span
                                        class="text-slate-600 border px-2 py-1 rounded-1 font-monospace">{{ $item['modality'] }}</span>
                                </td>
                                <td class="text-center font-monospace">{{ $item['count'] }}</td>
                                <td
                                    class="text-center font-monospace @if($item['avg_req_sample'] > 30) text-danger fw-bold @else text-slate-700 @endif">
                                    {{ $item['avg_req_sample'] }}
                                </td>
                                <td
                                    class="text-center font-monospace @if($item['avg_sample_result'] > 60) text-danger fw-bold @else text-slate-700 @endif">
                                    {{ $item['avg_sample_result'] }}
                                </td>
                                <td
                                    class="text-center bg-light-soft font-monospace fw-bold @if($item['avg_total'] > 120) text-danger @else text-custom @endif">
                                    {{ $item['avg_total'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i data-feather="inbox" style="width:24px; height: 24px;"
                                            class="opacity-50"></i></div>
                                    <div class="fw-bold" style="font-size: 0.75rem;">Data tidak ditemukan untuk periode ini.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4 no-print">
        <div class="col-md-12">
            <div class="card card-medizen border-0 shadow-sm">
                <div class="card-header border-bottom d-flex align-items-center pb-2 pt-3">
                    <div class="p-1 rounded bg-slate-100 me-2 text-slate-600">
                        <i data-feather="bar-chart-2" style="width:14px; height: 14px;"></i>
                    </div>
                    PERBANDINGAN EFISIENSI WAKTU (P-H) PER PEMERIKSAAN
                </div>
                <div class="card-body">
                    <div style="height: 400px;">
                        <canvas id="durationComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function () {
            const ctx = document.getElementById('durationComparisonChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($reportData->pluck('name')),
                        datasets: [
                            {
                                label: 'P-S (Menit)',
                                data: @json($reportData->pluck('avg_req_sample')),
                                backgroundColor: 'rgba(255, 206, 86, 0.6)',
                                borderColor: 'rgb(255, 206, 86)',
                                borderWidth: 1
                            },
                            {
                                label: 'S-H (Menit)',
                                data: @json($reportData->pluck('avg_sample_result')),
                                backgroundColor: 'rgba(24, 135, 84, 0.6)', // updated to #188754 alpha
                                borderColor: '#188754',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true, title: { display: true, text: 'Menit' } },
                            y: { stacked: true }
                        }
                    }
                });
            }
        });
    </script>
    <style>
        @media print {

            .sidebar,
            .top-navbar,
            .sidebar-overlay,
            .btn-primary,
            .btn-outline-custom,
            form,
            .no-print {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .card-header {
                padding-left: 0 !important;
            }

            table th {
                background-color: #fcfcfc !important;
                -webkit-print-color-adjust: exact;
            }

            body {
                background-color: white !important;
            }
        }
    </style>
@endpush