@extends('layouts.app')
@section('title', isset($report) ? 'Edit Laporan' : 'Buat Laporan')
@section('page-title', isset($report) ? 'Edit Hasil Radiologi' : 'Tulis Hasil Radiologi')

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

        .table-medizen td {
            padding: 6px 12px;
            vertical-align: middle;
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
            font-size: 0.75rem;
            border-color: #e2e8f0;
            padding: 0.5rem 0.75rem;
            box-shadow: none !important;
        }

        .form-control-medizen:focus,
        .form-select-medizen:focus {
            border-color: #188754;
            background-color: #fff;
        }

        .label-medizen {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
    </style>

    @php $currentOrder = $report->order ?? $order; @endphp

    <div class="row layout-row mb-3 align-items-center">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-slate-800" style="font-size: 1.1rem; letter-spacing: -0.5px;">
                    {{ isset($report) ? 'EDIT LAPORAN HASIL BACA' : 'LAPORAN HASIL BACA' }}</h4>
                <p class="text-muted small mb-0" style="font-size: 0.7rem;">Order: <span
                        class="fw-bold font-monospace">{{ $currentOrder->order_number }}</span></p>
            </div>
            <a href="{{ route('orders.show', $currentOrder) }}"
                class="btn btn-medizen btn-light border d-flex align-items-center rounded-2 py-2">
                <i data-feather="arrow-left" style="width:14px;height:14px" class="me-1"></i> KEMBALI
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card card-medizen border-0 shadow-sm h-100">
                <div class="card-header border-bottom d-flex align-items-center pb-2 pt-3">
                    <div class="p-1 rounded bg-slate-100 me-2 text-slate-600">
                        <i data-feather="info" style="width:14px; height: 14px;"></i>
                    </div>
                    INFORMASI ORDER
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless table-medizen mb-0">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="text-muted">No. Order</td>
                                <td><strong class="font-monospace">{{ $currentOrder->order_number }}</strong></td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Pasien</td>
                                <td><strong class="text-slate-800">{{ $currentOrder->patient->nama ?? '-' }}</strong></td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">No. RM</td>
                                <td><span
                                        class="font-monospace text-slate-700">{{ $currentOrder->patient->no_rm ?? '-' }}</span>
                                </td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Modalitas</td>
                                <td><span
                                        class="text-slate-600 border border-slate-200 px-2 rounded-1">{{ $currentOrder->modality }}</span>
                                </td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Pemeriksaan</td>
                                <td class="fw-bold text-slate-700">{{ $currentOrder->examinationType->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal</td>
                                <td class="text-slate-700">{{ $currentOrder->formatted_date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-medizen border-0 shadow-sm">
                <div class="card-header border-bottom d-flex align-items-center pb-2 pt-3">
                    <div class="p-1 rounded bg-custom-soft me-2 text-custom">
                        <i data-feather="edit-3" style="width:14px; height: 14px;"></i>
                    </div>
                    TULIS HASIL / EXPERTISE
                </div>
                <div class="card-body p-4 bg-light-soft">
                    <form method="POST"
                        action="{{ isset($report) ? route('reports.update', $report) : route('reports.store', $currentOrder) }}">
                        @csrf
                        @if(isset($report)) @method('PUT') @endif

                        <div class="mb-3 px-3 py-3 bg-white border shadow-sm" style="border-radius: 4px;">
                            <label class="label-medizen">DOKTER RADIOLOGI <span class="text-danger">*</span></label>
                            <select name="dokter_id"
                                class="form-select form-select-medizen bg-light-soft mt-1 fw-bold text-slate-700" required>
                                <option value="">-- SILAKAN PILIH DOKTER --</option>
                                @foreach($doctors as $d)
                                    <option value="{{ $d->id }}" {{ old('dokter_id', $report->dokter_id ?? '') == $d->id ? 'selected' : '' }}>{{ strtoupper($d->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 px-3 py-3 bg-white border shadow-sm" style="border-radius: 4px;">
                            <label class="label-medizen d-flex align-items-center">
                                <i data-feather="file-text" style="width:12px; height:12px;"
                                    class="me-1 text-slate-400"></i> HASIL PEMERIKSAAN <span
                                    class="text-danger ms-1">*</span>
                            </label>
                            <textarea name="hasil" class="form-control form-control-medizen bg-light-soft mt-1" rows="8"
                                placeholder="Ketik hasil analisa radiologi di sini..." required
                                style="resize: vertical;">{{ old('hasil', $report->hasil ?? '') }}</textarea>
                        </div>

                        <div class="mb-4 px-3 py-3 bg-white border shadow-sm border-start border-custom border-3"
                            style="border-radius: 4px;">
                            <label class="label-medizen d-flex align-items-center text-custom">
                                <i data-feather="check-square" style="width:12px; height:12px;" class="me-1"></i> KESIMPULAN
                                KESELURUHAN <span class="text-danger ms-1">*</span>
                            </label>
                            <textarea name="kesimpulan" class="form-control form-control-medizen bg-light-soft mt-1 fw-bold"
                                rows="3" placeholder="Ketik kesimpulan pemeriksaan di sini..." required
                                style="resize: none;">{{ old('kesimpulan', $report->kesimpulan ?? '') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end align-items-center bg-white p-3 border shadow-sm"
                            style="border-radius: 4px;">
                            <span class="text-muted small me-3" style="font-size: 0.65rem;">Pastikan laporan telah ditinjau
                                kembali sebelum menyimpan.</span>
                            <button type="submit" class="btn btn-medizen btn-primary-custom px-4 py-2">
                                <i data-feather="save" style="width:14px;height:14px" class="me-1"></i> SIMPAN LAPORAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection