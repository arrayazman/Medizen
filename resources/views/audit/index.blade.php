@extends('layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Security Audit Trail')

@section('content')
    <div class="card card-medizen">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">System Audit Trail</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">MONITORING SYSTEM CHANGES AND USER ACTIVITIES</div>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-dark-soft text-slate-700 px-3 py-2 border fw-bold" style="font-size: 0.75rem;">
                    TOTAL: {{ number_format($audits->total()) }} LOGS
                </span>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filter Bar -->
            <div class="p-3 bg-light-soft border-bottom">
                <form action="{{ route('audit.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="position-relative">
                            <i data-feather="user" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                                style="width: 14px; height: 14px;"></i>
                            <input type="text" name="user" class="form-control filter-box-medizen ps-5"
                                placeholder="Cari berdasarkan user..." value="{{ request('user') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="action" class="form-select filter-box-medizen">
                            <option value="">Aksi: SEMUA</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>CREATED</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>UPDATED</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>DELETED</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control filter-box-medizen"
                            value="{{ request('date') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold py-2"
                            style="font-size: 0.7rem;">FILTER LOGS</button>
                        @if(request()->anyFilled(['user', 'action', 'date']))
                            <a href="{{ route('audit.index') }}" class="btn btn-light btn-sm border fw-bold ms-1 py-2"
                                style="font-size: 0.7rem;">RESET</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Timestamp</th>
                            <th>Operator (User)</th>
                            <th class="text-center">Action Type</th>
                            <th>Resource affected</th>
                            <th>Detailed Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td class="text-slate-600">
                                    <div class="fw-bold">{{ $audit->created_at->format('d M Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">{{ $audit->created_at->format('H:i:s') }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $audit->user->name ?? 'System Process' }}</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">UID: #{{ $audit->user_id ?? 'SYS' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if(in_array($audit->action, ['created', 'create']))
                                        <span class="badge bg-emerald-soft text-emerald px-2 py-1 border"
                                            style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">CREATED</span>
                                    @elseif(in_array($audit->action, ['updated', 'update']))
                                        <span class="badge bg-info-soft text-info px-2 py-1 border"
                                            style="font-size: 0.6rem; border-color: rgba(14, 165, 233, 0.2) !important;">UPDATED</span>
                                    @elseif(in_array($audit->action, ['deleted', 'delete']))
                                        <span class="badge bg-danger-soft text-danger px-2 py-1 border"
                                            style="font-size: 0.6rem; border-color: rgba(239, 68, 68, 0.2) !important;">DELETED</span>
                                    @else
                                        <span class="badge bg-light text-muted px-2 py-1 border"
                                            style="font-size: 0.6rem;">{{ strtoupper($audit->action) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-700" style="font-size: 0.75rem;">
                                        {{ class_basename($audit->model_type) }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">ID: {{ $audit->model_id }}</div>
                                </td>
                                <td>
                                    <div class="text-muted" style="font-size: 0.65rem; max-width: 300px;">
                                        @foreach(array_keys($audit->new_values ?? $audit->old_values ?? []) as $attribute)
                                            <span class="me-1">[{{ $attribute }}]</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="terminal" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">BELUM ADA AUDIT LOG</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($audits->withQueryString()->hasPages())
                <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                    <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                        SHOWING {{ $audits->firstItem() ?? 0 }}-{{ $audits->lastItem() ?? 0 }} OF {{ $audits->total() }} LOGS
                    </div>
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            @if($audits->onFirstPage())
                                <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">‹ PREV</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $audits->appends(request()->query())->previousPageUrl() }}">‹ PREV</a></li>
                            @endif
                            
                            @foreach($audits->getUrlRange(max(1, $audits->currentPage() - 2), min($audits->lastPage(), $audits->currentPage() + 2)) as $page => $url)
                                @if($page == $audits->currentPage())
                                    <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 0.6rem; font-weight: bold;">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 0.6rem;" href="{{ $audits->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if($audits->hasMorePages())
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $audits->appends(request()->query())->nextPageUrl() }}">NEXT ›</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">NEXT ›</span></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endsection
