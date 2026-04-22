@extends('layouts.app')
@section('title', 'Order Radiologi')
@section('page-title', 'Daftar Antrean Order')

@section('content')
    <div class="card card-medizen rounded-0 border-0 shadow-none">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Radiology Worklist</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">MONITORING & QUEUE MANAGEMENT SYSTEM</div>
            </div>
            @if(auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer']))
                <div class="d-flex gap-2">
                    <a href="{{ route('orders.export-csv', request()->all()) }}" class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0"
                        style="font-size: 0.7rem;">
                        <i data-feather="download" class="me-2" style="width: 14px;"></i> EXPORT EXCEL
                    </a>
                    <a href="{{ route('orders.create') }}" class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0"
                        style="font-size: 0.7rem;">
                        <i data-feather="plus" class="me-2" style="width: 14px;"></i> CREATE NEW ORDER
                    </a>
                </div>
            @endif
        </div>

        <div class="card-body p-0">
            <!-- Integrated Filter Bar -->
            <div class="p-1 bg-light-soft border-bottom">
                <form method="GET" class="p-2">
                    <input type="hidden" name="tab" value="{{ request('tab', 'waiting') }}">
                    <div class="row g-1">
                        <div class="col-md-3 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">PATIENT / RM</label>
                            <div class="position-relative">
                                <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted" style="width: 10px;"></i>
                                <input type="text" name="search" class="form-control form-control-sm ps-4 rounded-0"
                                    placeholder="Search..." value="{{ request('search') }}" style="font-size: 0.6rem; height: 28px;">
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">MODALITY</label>
                            <select name="modality" class="form-select form-select-sm rounded-0" style="font-size: 0.6rem; height: 28px;">
                                <option value="">ALL</option>
                                @foreach($modalities as $m)
                                    <option value="{{ $m->code }}" {{ request('modality') == $m->code ? 'selected' : '' }}>{{ $m->code }}</option>
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
                            <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 flex-fill" style="font-size: 0.6rem; height: 28px;">
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

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 small">Accession & Order</th>
                            <th class="py-2 small">Patient</th>
                            <th class="text-center py-2 small">Modality & Exam</th>
                            <th class="text-center py-2 small">Schedule</th>
                            <th class="text-center py-2 small">Status</th>
                            <th class="text-end py-2 small">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $o)
                            <tr onclick="window.location='{{ route('orders.show', $o) }}'" style="cursor: pointer;">
                                <td class="py-2">
                                    <div class="fw-bold text-slate-800 small">#{{ $o->order_number }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">ACC: <span
                                            class="text-emerald fw-bold">{{ $o->accession_number }}</span></div>
                                </td>
                                <td class="py-2">
                                    <div class="fw-bold text-slate-700 small text-truncate" style="max-width: 120px;">{{ $o->patient->nama ?? '-' }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">RM: <span class="privacy-mask">{{ $o->patient->no_rm ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="text-center py-2">
                                    <span class="badge bg-emerald-soft text-emerald border px-1 py-0 mb-1"
                                        style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">{{ $o->modality }}</span>
                                    <div class="text-muted text-truncate mx-auto small" style="max-width: 100px; font-size: 0.6rem;">
                                        <span class="privacy-mask">{{ $o->examinationType->name ?? 'Standard Exam' }}</span>
                                    </div>
                                </td>
                                <td class="text-center py-2">
                                    <div class="fw-bold text-slate-700" style="font-size: 0.7rem;">{{ $o->formatted_date }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;"><i data-feather="clock" class="me-1"
                                            style="width: 10px;"></i>
                                        {{ \Carbon\Carbon::parse($o->scheduled_time)->format('H:i') }}</div>
                                </td>
                                <td class="text-center py-2">
                                    <div class="mb-1" style="transform: scale(0.85);">
                                        @if($o->priority == 'STAT')
                                            <span class="badge-modern bg-danger text-white">STAT</span>
                                        @elseif($o->priority == 'URGENT')
                                            <span class="badge-modern bg-warning text-dark">URG</span>
                                        @else
                                            <span class="badge-modern bg-light text-muted border">RTN</span>
                                        @endif
                                    </div>
                                    <div style="transform: scale(0.85);">
                                        {!! $o->status_badge !!}
                                    </div>
                                </td>
                                <td class="text-end py-2" onclick="event.stopPropagation();">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('orders.show', $o) }}"
                                            class="btn btn-emerald-soft btn-sm p-1 border rounded-0" title="Order Details">
                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                        </a>
                                        @if($o->status == 'ORDERED' && auth()->user()->hasRole(['super_admin', 'admin_radiologi', 'radiografer']))
                                            <a href="{{ route('orders.edit', $o) }}"
                                                class="btn btn-dark btn-sm p-1 border rounded-0" title="Modify Order">
                                                <i data-feather="edit-3" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="clipboard" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">BELUM ADA ORDER RADIOLOGI</h6>
                                        <p class="small mb-0">Antrean kosong atau tidak ada data yang sesuai filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
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
        </div>
    </div>
@push('scripts')
    <script>
        // --- GLOBAL PERSISTENT FILTER ---
        $(document).ready(function() {
            const globalKey = 'medizen_global_filter';
            const urlParams = new URLSearchParams(window.location.search);

            if (!urlParams.has('search') && !urlParams.has('start_date')) {
                const saved = localStorage.getItem(globalKey);
                if (saved) {
                    const f = JSON.parse(saved);
                    // Map global to local
                    if (f.tgl1 || f.start_date) $('input[name="start_date"]').val(f.tgl1 || f.start_date);
                    if (f.tgl2 || f.end_date) $('input[name="end_date"]').val(f.tgl2 || f.end_date);
                    if (f.keyword || f.search) $('input[name="search"]').val(f.keyword || f.search);
                    if (f.modality) $('select[name="modality"]').val(f.modality);
                    if (f.tab) $('input[name="tab"]').val(f.tab);
                }
            }

            $('form').on('submit', function() {
                const filters = {
                    tgl1: $('input[name="start_date"]').val(),
                    tgl2: $('input[name="end_date"]').val(),
                    keyword: $('input[name="search"]').val(),
                    // Save originals
                    search: $('input[name="search"]').val(),
                    modality: $('select[name="modality"]').val(),
                    start_date: $('input[name="start_date"]').val(),
                    end_date: $('input[name="end_date"]').val(),
                    tab: $('input[name="tab"]').val()
                };
                localStorage.setItem(globalKey, JSON.stringify(filters));
            });
        });
        // --- END GLOBAL FILTER ---
    </script>
@endpush
@endsection