@extends('layouts.app')
@section('title', 'Master Dokter')
@section('page-title', 'Manajemen Staff Medis')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 13px; letter-spacing: 1px;">
            MEDICAL STAFF DIRECTORY
        </div>
        <a href="{{ route('master.doctors.create') }}" class="btn btn-emerald medizen-btn-minimal">
            <i data-feather="plus" class="me-1" style="width: 14px;"></i> ADD NEW PHYSICIAN
        </a>
    </div>

    <div class="medizen-card-minimal">
        <!-- Search Area -->
        <div class="p-2 border-bottom">
            <form action="{{ route('master.doctors.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="position-relative">
                        <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                            style="width: 12px;"></i>
                        <input type="text" name="search" class="form-control medizen-input-minimal ps-5"
                            placeholder="Search by Name or SIP Number..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark medizen-btn-minimal w-100">FILTER</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="25%">PHYSICIAN NAME</th>
                        <th width="20%">SPECIALIZATION</th>
                        <th width="15%">SIP NUMBER</th>
                        <th width="15%">CONTACT</th>
                        <th width="10%" class="text-center">STATUS</th>
                        <th width="10%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $index => $d)
                        <tr>
                            <td class="text-muted fw-bold">
                                {{ str_pad($doctors->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $d->name }}</div>
                                <div class="text-muted" style="font-size: 10px;">ID: #{{ $d->id }}</div>
                            </td>
                            <td>
                                <span class="text-uppercase fw-bold text-slate-600" style="font-size: 11px;">
                                    {{ $d->specialization ?? 'General Physician' }}
                                </span>
                            </td>
                            <td class="fw-bold text-slate-700">{{ $d->sip_number ?? '-' }}</td>
                            <td>
                                <div class="text-slate-600 mb-0" style="font-size: 11px;">{{ $d->phone ?? '-' }}</div>
                                <div class="text-muted small">{{ $d->email ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                @if($d->is_active)
                                    <span class="medizen-indicator active">ACTIVE</span>
                                @else
                                    <span class="medizen-indicator">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('master.doctors.edit', $d) }}"
                                        class="btn btn-outline-primary medizen-btn-action-minimal" title="Edit">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </a>
                                    <form action="{{ route('master.doctors.destroy', $d) }}" method="POST"
                                        class="d-inline swal-confirm" data-swal-title="Delete Doctor?"
                                        data-swal-text="Data dokter {{ $d->name }} akan dihapus!">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger medizen-btn-action-minimal"
                                            title="Delete">
                                            <i data-feather="trash-2" style="width: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 uppercase text-muted small">BELUM ADA DATA DOKTER</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($doctors->hasPages())
            <div class="p-2 border-top d-flex justify-content-between align-items-center bg-light-soft">
                <div class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">
                    SHOWING {{ $doctors->firstItem() }} - {{ $doctors->lastItem() }} OF {{ $doctors->total() }} PHYSICIANS
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        @if($doctors->onFirstPage())
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">PREV</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $doctors->appends(request()->query())->previousPageUrl() }}">PREV</a></li>
                        @endif
                        
                        @foreach($doctors->getUrlRange(max(1, $doctors->currentPage() - 1), min($doctors->lastPage(), $doctors->currentPage() + 1)) as $page => $url)
                            @if($page == $doctors->currentPage())
                                <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 10px; font-weight: bold;">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 10px;" href="{{ $doctors->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        @if($doctors->hasMorePages())
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $doctors->appends(request()->query())->nextPageUrl() }}">NEXT</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">NEXT</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
@endsection