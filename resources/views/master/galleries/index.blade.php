@extends('layouts.app')
@section('title', 'Master Galeri')
@section('page-title', 'Manajemen Konten & Edukasi')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 13px; letter-spacing: 1px;">
            GALLERY & MEDIA REPOSITORY
        </div>
        <a href="{{ route('master.galleries.create') }}" class="btn btn-emerald medizen-btn-minimal">
            <i data-feather="plus" class="me-1" style="width: 14px;"></i> TAMBAH GALERI
        </a>
    </div>

    <div class="medizen-card-minimal">
        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">NAMA GALERI</th>
                        <th width="15%">TIPE</th>
                        <th width="15%" class="text-center">ITEM</th>
                        <th width="15%" class="text-center">STATUS DISPLAY</th>
                        <th width="20%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($galleries as $index => $g)
                        <tr>
                            <td class="text-muted fw-bold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $g->name }}</div>
                            </td>
                            <td>
                                @if($g->type === 'photo')
                                    <span class="badge bg-info-subtle text-info border-info-subtle text-uppercase px-2 py-1" style="font-size: 10px;">PHOTO</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border-warning-subtle text-uppercase px-2 py-1" style="font-size: 10px;">VIDEO</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-slate-700">{{ $g->items_count }}</span> <small class="text-muted">Files</small>
                            </td>
                            <td class="text-center">
                                @if($g->is_active)
                                    <span class="medizen-indicator active">ACTIVE DISPLAY</span>
                                @else
                                    <form action="{{ route('master.galleries.activate', $g) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-link p-0 text-decoration-none text-muted small fw-bold">SET AKTIF</button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('master.galleries.items', $g) }}" class="btn btn-outline-info medizen-btn-action-minimal" title="Kelola Item">
                                        <i data-feather="image" style="width: 12px;"></i>
                                    </a>
                                    <a href="{{ route('master.galleries.edit', $g) }}" class="btn btn-outline-primary medizen-btn-action-minimal" title="Edit">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </a>
                                    <form action="{{ route('master.galleries.destroy', $g) }}" method="POST" class="d-inline swal-confirm" data-swal-title="Hapus Galeri?" data-swal-text="Hapus galeri {{ $g->name }} beserta semua isinya?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger medizen-btn-action-minimal" title="Delete">
                                            <i data-feather="trash-2" style="width: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 uppercase text-muted small">BELUM ADA DATA GALERI</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
