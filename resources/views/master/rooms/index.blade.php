@extends('layouts.app')
@section('title', 'Daftar Ruangan')
@section('page-title', 'Manajemen Fasilitas')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 13px; letter-spacing: 1px;">
            FACILITY & ROOM DIRECTORY
        </div>
        <a href="{{ route('master.rooms.create') }}" class="btn btn-emerald medizen-btn-minimal">
            <i data-feather="plus" class="me-1" style="width: 14px;"></i> TAMBAH RUANGAN
        </a>
    </div>

    <div class="medizen-card-minimal">
        <!-- Search Area -->
        <div class="p-2 border-bottom">
            <form action="{{ route('master.rooms.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="position-relative">
                        <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                            style="width: 12px;"></i>
                        <input type="text" name="search" class="form-control medizen-input-minimal ps-5"
                            placeholder="Cari berdasarkan nama atau kode ruangan..." value="{{ request('search') }}">
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
                        <th width="25%">NAMA RUANGAN</th>
                        <th width="15%">KODE</th>
                        <th width="35%">DESKRIPSI</th>
                        <th width="10%" class="text-center">STATUS</th>
                        <th width="10%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $index => $r)
                        <tr>
                            <td class="text-muted fw-bold">
                                {{ str_pad($rooms->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $r->name }}</div>
                            </td>
                            <td><code class="text-slate-600 fw-bold">{{ $r->code }}</code></td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($r->description ?? '-', 60) }}</td>
                            <td class="text-center">
                                @if($r->is_active)
                                    <span class="medizen-indicator active">ACTIVE</span>
                                @else
                                    <span class="medizen-indicator">INACTIVE</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('master.rooms.edit', $r) }}"
                                        class="btn btn-outline-primary medizen-btn-action-minimal" title="Edit">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </a>
                                    <form action="{{ route('master.rooms.destroy', $r) }}" method="POST"
                                        class="d-inline swal-confirm" data-swal-title="Hapus Ruangan?"
                                        data-swal-text="Hapus data ruangan {{ $r->name }}?">
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
                            <td colspan="6" class="text-center py-5 uppercase text-muted small">BELUM ADA DATA RUANGAN</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rooms->hasPages())
            <div class="p-2 border-top d-flex justify-content-between align-items-center bg-light-soft">
                <div class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">
                    SHOWING {{ $rooms->firstItem() }} - {{ $rooms->lastItem() }} OF {{ $rooms->total() }} ROOMS
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        @if($rooms->onFirstPage())
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">PREV</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $rooms->appends(request()->query())->previousPageUrl() }}">PREV</a></li>
                        @endif
                        
                        @foreach($rooms->getUrlRange(max(1, $rooms->currentPage() - 1), min($rooms->lastPage(), $rooms->currentPage() + 1)) as $page => $url)
                            @if($page == $rooms->currentPage())
                                <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 10px; font-weight: bold;">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 10px;" href="{{ $rooms->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        @if($rooms->hasMorePages())
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $rooms->appends(request()->query())->nextPageUrl() }}">NEXT</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">NEXT</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
@endsection