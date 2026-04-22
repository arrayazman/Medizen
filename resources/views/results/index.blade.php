@extends('layouts.app')
@section('title', 'Worklist Expertise')
@section('page-title', 'Pemeriksaan Radiologi (Expertise)')

@section('content')
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6 mb-2 mb-sm-0">
            <h5 class="mb-0 fw-bold text-slate-800" style="letter-spacing: -0.5px; font-size: 1.1rem;">Reporting Worklist
            </h5>
        </div>
        <div class="col-sm-6 d-flex justify-content-sm-end">
            <div class="p-1 bg-light border rounded-0 d-flex gap-1 w-100 w-sm-auto overflow-auto"
                style="white-space: nowrap;">
                <button class="btn btn-sm px-2 fw-bold border-0 text-muted hover-bg-light rounded-0 flex-fill flex-sm-none"
                    type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false"
                    style="font-size: 0.65rem;">
                    <i data-feather="filter" class="me-1" style="width: 12px;"></i> FILTER
                </button>
                <div class="vr my-1 bg-secondary opacity-25"></div>
                <a class="btn btn-sm px-2 fw-bold {{ request('tab', 'waiting') == 'waiting' ? 'btn-white border text-emerald shadow-none' : 'text-muted border-0 hover-bg-light' }} rounded-0 flex-fill flex-sm-none"
                    href="{{ request()->fullUrlWithQuery(['tab' => 'waiting']) }}" style="font-size: 0.65rem;">
                    <i data-feather="clock" class="me-1" style="width: 12px;"></i> WAITING
                </a>
                <a class="btn btn-sm px-2 fw-bold {{ request('tab') == 'completed' ? 'active btn-white border text-emerald shadow-none' : 'text-muted border-0 hover-bg-light' }} rounded-0 flex-fill flex-sm-none"
                    href="{{ request()->fullUrlWithQuery(['tab' => 'completed']) }}" style="font-size: 0.65rem;">
                    <i data-feather="check" class="me-1" style="width: 12px;"></i> VALID
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="collapse {{ request()->anyFilled(['search', 'modality', 'start_date', 'end_date']) ? 'show' : '' }} mb-3"
        id="filterCollapse">
        <div class="card card-medizen border rounded-0">
            <div class="card-body p-2 bg-white border-bottom rounded-0 overflow-hidden position-relative">
                {{-- Decorative pattern --}}

                <form method="GET" class="p-2">
                    <input type="hidden" name="tab" value="{{ request('tab', 'waiting') }}">
                    <div class="row g-1">
                        <div class="col-md-3 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">PATIENT / RM</label>
                            <div class="position-relative">
                                <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                    style="width: 10px;"></i>
                                <input type="text" name="search" class="form-control form-control-sm ps-4 rounded-0"
                                    placeholder="Search..." value="{{ request('search') }}"
                                    style="font-size: 0.6rem; height: 28px;">
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">MODALITY</label>
                            <select name="modality" class="form-select form-select-sm rounded-0"
                                style="font-size: 0.6rem; height: 28px;">
                                <option value="">ALL</option>
                                @foreach($modalities as $m)
                                    <option value="{{ $m->code }}" {{ request('modality') == $m->code ? 'selected' : '' }}>
                                        {{ $m->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">DATE RANGE</label>
                            <div class="input-group input-group-sm">
                                <input type="date" name="start_date" class="form-control rounded-0"
                                    value="{{ request('start_date') }}" style="font-size: 0.6rem; height: 28px;">
                                <input type="date" name="end_date" class="form-control rounded-0"
                                    value="{{ request('end_date') }}" style="font-size: 0.6rem; height: 28px;">
                            </div>
                        </div>
                        <div class="col-md-3 col-12 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 flex-fill"
                                style="font-size: 0.6rem; height: 28px;">
                                <i data-feather="check" class="me-1" style="width: 10px;"></i> APPLY
                            </button>
                            @if(request()->anyFilled(['search', 'modality', 'start_date', 'end_date']))
                                <a href="{{ route('results.index', ['tab' => request('tab', 'waiting'), 'clear_filters' => 1]) }}"
                                    class="btn btn-light border btn-sm rounded-0" title="Reset" style="height: 28px;">
                                    <i data-feather="refresh-cw" style="width: 10px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card rounded-0 border-0 shadow-none">
        <div class="table-responsive">
            <table class="table table-medizen mb-0">
                <thead>
                    <tr>
                        <th class="py-2 small" style="width: 120px;">Exam Date</th>
                        <th class="py-2 small">Patient</th>
                        <th class="py-2 small">Accession & Study</th>
                        <th class="text-center py-2 small">Modality</th>
                        <th class="text-center py-2 small">Physician</th>
                        <th class="text-center py-2 small">Status</th>
                        <th class="text-end py-2 small">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr onclick="window.location='{{ route('results.edit', $o) }}'" style="cursor: pointer;">
                            <td class="py-2">
                                <div class="fw-bold text-slate-800 small">{{ $o->formatted_date }}</div>
                                <div class="text-muted" style="font-size: 0.6rem;"><i data-feather="clock" class="me-1"
                                        style="width: 10px;"></i>
                                    {{ \Carbon\Carbon::parse($o->scheduled_time)->format('H:i') }}
                                </div>
                            </td>
                            <td class="py-2">
                                <div class="fw-bold text-slate-700 small text-truncate" style="max-width: 120px;">
                                    {{ $o->patient->nama ?? '-' }}
                                </div>
                                <div class="text-muted" style="font-size: 0.6rem;">RM: {{ $o->patient->no_rm ?? '-' }}</div>
                            </td>
                            <td class="py-2">
                                <div class="fw-bold text-emerald" style="font-size: 0.65rem;">{{ $o->accession_number }}</div>
                                <div class="text-muted text-truncate" style="max-width: 150px; font-size: 0.6rem;">
                                    {{ $o->examinationType->name ?? 'Standard Procedure' }}
                                </div>
                            </td>
                            <td class="text-center py-2">
                                <span class="badge bg-emerald-soft text-emerald border px-1 py-0"
                                    style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">
                                    {{ $o->modality }}
                                </span>
                            </td>
                            <td class="text-center py-2">
                                <div class="text-slate-700" style="font-size: 0.65rem;">
                                    {{ $o->result->doctor->name ?? ($o->referringDoctor->name ?? '-') }}
                                </div>
                            </td>
                            <td class="text-center py-2">
                                <div style="transform: scale(0.85);">
                                    {!! $o->status_badge !!}
                                </div>
                            </td>
                            <td class="text-end py-2" onclick="event.stopPropagation();">
                                @if(request('tab', 'waiting') == 'waiting')
                                    <a href="{{ route('results.edit', $o) }}" class="btn btn-emerald btn-sm px-3 fw-bold rounded-0"
                                        style="font-size: 0.65rem;">
                                        <i data-feather="edit-3" class="me-1" style="width: 12px;"></i> WRITE REPORT
                                    </a>
                                @else
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('results.edit', $o) }}"
                                            class="btn btn-emerald-soft btn-sm px-2 py-1 border rounded-0" title="Edit Result"
                                            style="font-size: 0.6rem;">
                                            <i data-feather="edit-2" class="me-1" style="width: 12px; height: 12px;"></i> EDIT
                                        </a>
                                        <a href="{{ route('orders.print', $o) }}" target="_blank"
                                            class="btn btn-dark btn-sm px-2 py-1 border rounded-0" title="Print Report"
                                            style="font-size: 0.6rem;">
                                            <i data-feather="printer" class="me-1" style="width: 12px; height: 12px;"></i> PRINT
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center opacity-50">
                                    <i data-feather="file-text" style="width: 48px; height: 48px;"
                                        class="mb-3 text-emerald"></i>
                                    <h6 class="fw-bold">BELUM ADA LAPORAN
                                        {{ request('tab', 'waiting') == 'waiting' ? 'PENDING' : 'VALID' }}
                                    </h6>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    @if($orders->withQueryString()->hasPages())
        <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
            <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                SHOWING {{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }} OF {{ $orders->total() }} TOTAL ENTRIES
            </div>
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0 gap-1">
                    @if($orders->onFirstPage())
                        <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">‹ PREV</span></li>
                    @else
                        <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $orders->appends(request()->query())->previousPageUrl() }}">‹ PREV</a></li>
                    @endif
                    
                    @foreach($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                        @if($page == $orders->currentPage())
                            <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 0.6rem; font-weight: bold;">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 0.6rem;" href="{{ $orders->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                        @endif
                    @endforeach

                    @if($orders->hasMorePages())
                        <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $orders->appends(request()->query())->nextPageUrl() }}">NEXT ›</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">NEXT ›</span></li>
                    @endif
                </ul>
            </nav>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .btn-white {
            background-color: #fff;
            border-color: #dee2e6;
            color: #1e293b;
        }

        .hover-bg-light:hover {
            background-color: #f1f5f9;
        }

        .bg-light-soft {
            background-color: #f8fafc;
        }

        .bg-emerald-soft {
            background-color: rgba(16, 185, 129, 0.1);
        }

        .text-emerald {
            color: #10b981 !important;
        }

        .btn-emerald {
            background-color: #10b981;
            color: white;
            border: none;
        }

        .btn-emerald:hover {
            background-color: #059669;
            color: white;
        }

        .input-group-medizen .form-control {
            border-right: none;
        }

        .input-group-medizen .form-control+.input-group-text+.form-control {
            border-left: none;
            border-right: 1px solid #e2e8f0;
        }

        .group-hover:focus-within i {
            color: #10b981 !important;
        }

        /* Modern pagination styling if needed */
        .pagination-medizen .page-link {
            border-radius: 4px;
            margin: 0 2px;
            font-weight: 600;
        }
    </style>
@endpush