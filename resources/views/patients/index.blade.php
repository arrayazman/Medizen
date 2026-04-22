@extends('layouts.app')
@section('title', 'Data Pasien')
@section('page-title', 'Database Pasien')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 13px; letter-spacing: 1px;">
            PATIENT ARCHIVE & MEDICAL RECORDS
        </div>
        <a href="{{ route('patients.create') }}" class="btn btn-emerald medizen-btn-minimal">
            <i data-feather="user-plus" class="me-1" style="width: 14px;"></i> REGISTER NEW PATIENT
        </a>
    </div>

    <div class="medizen-card-minimal">
        <!-- Search Area -->
        <div class="p-2 border-bottom">
            <form action="{{ route('patients.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="position-relative">
                        <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                            style="width: 12px;"></i>
                        <input type="text" name="search" class="form-control medizen-input-minimal ps-5"
                            placeholder="Search by RM Number, NIK, or Patient Name..." value="{{ request('search') }}">
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
                        <th width="12%">RM NUMBER</th>
                        <th width="25%">PATIENT IDENTITY</th>
                        <th width="10%" class="text-center">GENDER</th>
                        <th width="15%" class="text-center">BIRTH / AGE</th>
                        <th width="23%">CONTACT & ADDRESS</th>
                        <th width="10%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $index => $item)
                        <tr>
                            <td class="text-muted fw-bold">
                                {{ str_pad($patients->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <code class="text-emerald fw-bold privacy-mask">{{ $item->no_rm }}</code>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ strtoupper($item->nama) }}</div>
                                <div class="text-muted" style="font-size: 10px;">NIK: <span class="privacy-mask">{{ $item->nik ?? '-' }}</span></div>
                            </td>
                            <td class="text-center">
                                @if($item->jenis_kelamin == 'L')
                                    <span class="medizen-indicator active">MALE</span>
                                @else
                                    <span class="medizen-indicator active text-danger">FEMALE</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-slate-700 privacy-mask">
                                    {{ $item->tgl_lahir ? $item->tgl_lahir->format('d/m/Y') : '-' }}</div>
                                <div class="text-muted small" style="font-size: 10px;">{{ strtoupper($item->umur) }}</div>
                            </td>
                            <td>
                                <div class="text-slate-700 fw-bold privacy-mask" style="font-size: 11px;">{{ $item->no_hp ?? '-' }}</div>
                                <div class="text-muted text-truncate privacy-mask" style="max-width: 200px; font-size: 10px;">
                                    {{ strtoupper($item->alamat ?? '-') }}
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('patients.show', $item) }}"
                                        class="btn btn-outline-emerald medizen-btn-action-minimal" title="Detail">
                                        <i data-feather="folder" style="width: 12px;"></i>
                                    </a>
                                    <a href="{{ route('patients.edit', $item) }}"
                                        class="btn btn-outline-primary medizen-btn-action-minimal" title="Edit">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 uppercase text-muted small">BELUM ADA DATA PASIEN TERDAFTAR
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="p-2 border-top d-flex justify-content-between align-items-center bg-light-soft">
                <div class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">
                    SHOWING {{ $patients->firstItem() }} - {{ $patients->lastItem() }} OF {{ $patients->total() }} ARCHIVES
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        @if($patients->onFirstPage())
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">PREV</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $patients->appends(request()->query())->previousPageUrl() }}">PREV</a></li>
                        @endif
                        
                        @foreach($patients->getUrlRange(max(1, $patients->currentPage() - 1), min($patients->lastPage(), $patients->currentPage() + 1)) as $page => $url)
                            @if($page == $patients->currentPage())
                                <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 10px; font-weight: bold;">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 10px;" href="{{ $patients->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        @if($patients->hasMorePages())
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $patients->appends(request()->query())->nextPageUrl() }}">NEXT</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">NEXT</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
@endsection