@extends('layouts.app')
@section('title', 'Laporan Lama Pelayanan')
@section('page-title', 'Lama Pelayanan')

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
                <h4 class="fw-bold mb-0 text-slate-800" style="font-size: 1.1rem; letter-spacing: -0.5px;">LAPORAN LAMA
                    PELAYANAN</h4>
                <p class="text-muted small mb-0" style="font-size: 0.7rem;">Pemantauan waktu layanan dari permintaan hingga
                    hasil diberikan.</p>
            </div>
            <button type="button" class="btn btn-medizen btn-outline-custom d-flex align-items-center"
                onclick="window.print()">
                <i data-feather="printer" style="width:14px;height:14px" class="me-1"></i> CETAK LAPORAN
            </button>
        </div>
    </div>

    <div class="card card-medizen mb-3 p-3 bg-light-soft border-0 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.duration') }}" class="row g-2 align-items-end">
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
                    <i data-feather="clock" style="width:14px; height: 14px;"></i>
                </div>
                DATA LAMA PELAYANAN
            </div>
            <span class="text-slate-600 border px-2 py-1 rounded-1 fw-bold font-monospace"
                style="font-size: 0.65rem;">{{ count($orders) }} DATA</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr class="text-center align-middle">
                            <th rowspan="2" style="width: 40px;">NO</th>
                            <th rowspan="2">RM / PASIEN</th>
                            <th rowspan="2">DOKTER PENGIRIM</th>
                            <th rowspan="2">NO. ORDER</th>
                            <th colspan="3" class="border-bottom-0 pb-1">JADWAL / WAKTU</th>
                            <th colspan="3" class="border-bottom-0 pb-1">DURASI (MENIT)</th>
                        </tr>
                        <tr class="text-center align-middle">
                            <th style="width: 100px; border-top: 1px solid #f1f5f9;">PERMINTAAN</th>
                            <th style="width: 100px; border-top: 1px solid #f1f5f9;">SAMPEL</th>
                            <th style="width: 100px; border-top: 1px solid #f1f5f9;">HASIL</th>
                            <th style="width: 45px; border-top: 1px solid #f1f5f9;" title="Permintaan ke Sampel">P-S</th>
                            <th style="width: 45px; border-top: 1px solid #f1f5f9;" title="Sampel ke Hasil">S-H</th>
                            <th style="width: 45px; border-top: 1px solid #f1f5f9;" title="Permintaan ke Hasil">P-H</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $index => $order)
                            <tr>
                                <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-slate-800" style="font-size: 0.75rem;">{{ $order->patient->nama }}
                                    </div>
                                    <div class="text-muted font-monospace" style="font-size: 0.6rem; letter-spacing: 0.3px;">RM:
                                        {{ $order->patient->no_rm }}</div>
                                </td>
                                <td>
                                    <div class="text-slate-700 fw-semibold text-truncate" style="max-width: 120px;"
                                        title="{{ $order->referringDoctor->name ?? '-' }}">
                                        {{ $order->referringDoctor->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-center"><code
                                        class="text-slate-700 bg-light px-1 py-0 border rounded-1 fw-bold">{{ $order->order_number }}</code>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="d-block fw-bold text-slate-700">{{ $order->created_at->format('Y-m-d') }}</span>
                                    <span class="text-muted"
                                        style="font-size: 0.6rem;">{{ $order->created_at->format('H:i') }}</span>
                                </td>
                                <td class="text-center">
                                    @if($order->waktu_sample)
                                        <span
                                            class="d-block fw-bold text-slate-700">{{ \Carbon\Carbon::parse($order->waktu_sample)->format('Y-m-d') }}</span>
                                        <span class="text-muted"
                                            style="font-size: 0.6rem;">{{ \Carbon\Carbon::parse($order->waktu_sample)->format('H:i') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($order->result?->waktu_hasil)
                                        <span
                                            class="d-block fw-bold text-slate-700">{{ \Carbon\Carbon::parse($order->result->waktu_hasil)->format('Y-m-d') }}</span>
                                        <span class="text-muted"
                                            style="font-size: 0.6rem;">{{ \Carbon\Carbon::parse($order->result->waktu_hasil)->format('H:i') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td
                                    class="text-center fw-bold @if(($order->duration_req_sample ?? 0) > 60) text-danger @else text-slate-700 @endif">
                                    {{ $order->duration_req_sample ?? '-' }}</td>
                                <td
                                    class="text-center fw-bold @if(($order->duration_sample_result ?? 0) > 60) text-danger @else text-slate-700 @endif">
                                    {{ $order->duration_sample_result ?? '-' }}</td>
                                <td
                                    class="text-center fw-bold @if(($order->duration_total ?? 0) > 120) text-danger @else text-custom @endif">
                                    {{ $order->duration_total ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <div class="mb-2"><i data-feather="inbox" style="width:24px; height: 24px;"
                                            class="opacity-50"></i></div>
                                    <div class="fw-bold" style="font-size: 0.75rem;">Tidak ada data untuk periode ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($orders) > 0)
                        <tfoot class="bg-light-soft fw-bold border-top" style="border-top-width: 2px !important;">
                            <tr>
                                <td colspan="7" class="text-end py-2 text-slate-600" style="font-size: 0.65rem;">RATA-RATA
                                    (MENIT):</td>
                                <td class="text-center py-2 text-slate-800">{{ $stats['avg_request_to_sample'] }}</td>
                                <td class="text-center py-2 text-slate-800">{{ $stats['avg_sample_to_result'] }}</td>
                                <td class="text-center py-2 text-custom">{{ $stats['avg_total'] }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card card-medizen border-0 shadow-sm">
                <div class="card-header border-bottom d-flex align-items-center pb-2 pt-3">
                    <div class="p-1 rounded bg-slate-100 me-2 text-slate-600">
                        <i data-feather="pie-chart" style="width:14px; height:14px;"></i>
                    </div>
                    ANALISIS DISTRIBUSI WAKTU PELAYANAN
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-medizen mb-0">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th class="text-start ps-4" style="width: 25%;">KATEGORI DURASI</th>
                                    <th style="width: 25%;">PERMINTAAN ➔ SAMPEL (P-S)</th>
                                    <th style="width: 25%;">SAMPEL ➔ HASIL (S-H)</th>
                                    <th style="width: 25%;">PERMINTAAN ➔ HASIL (P-H)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center">
                                    <td class="text-start ps-4 fw-bold text-slate-700">0 - 15 Menit</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['request_to_sample']['0-15'] }}</td>
                                    <td class="font-monospace fw-bold">{{ $stats['breakdown']['sample_to_result']['0-15'] }}
                                    </td>
                                    <td class="font-monospace fw-bold text-custom">
                                        {{ $stats['breakdown']['total']['0-15'] }}</td>
                                </tr>
                                <tr class="text-center">
                                    <td class="text-start ps-4 fw-bold text-slate-700">&gt; 15 - 30 Menit</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['request_to_sample']['15-30'] }}</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['sample_to_result']['15-30'] }}</td>
                                    <td class="font-monospace fw-bold text-custom">
                                        {{ $stats['breakdown']['total']['15-30'] }}</td>
                                </tr>
                                <tr class="text-center">
                                    <td class="text-start ps-4 fw-bold text-slate-700">&gt; 30 - 60 Menit</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['request_to_sample']['30-60'] }}</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['sample_to_result']['30-60'] }}</td>
                                    <td class="font-monospace fw-bold text-warning text-darken">
                                        {{ $stats['breakdown']['total']['30-60'] }}</td>
                                </tr>
                                <tr class="text-center">
                                    <td class="text-start ps-4 fw-bold text-slate-700">&gt; 60 - 120 Menit</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['request_to_sample']['60-120'] }}</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['sample_to_result']['60-120'] }}</td>
                                    <td class="font-monospace fw-bold text-danger">
                                        {{ $stats['breakdown']['total']['60-120'] }}</td>
                                </tr>
                                <tr class="text-center">
                                    <td class="text-start ps-4 fw-bold text-slate-700">&gt; 120 Menit</td>
                                    <td class="font-monospace fw-bold">
                                        {{ $stats['breakdown']['request_to_sample']['>120'] }}</td>
                                    <td class="font-monospace fw-bold">{{ $stats['breakdown']['sample_to_result']['>120'] }}
                                    </td>
                                    <td class="font-monospace fw-bold text-danger">
                                        {{ $stats['breakdown']['total']['>120'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
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