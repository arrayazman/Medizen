@extends('layouts.app')
@section('title', 'Kelola Media Galeri')
@section('page-title', 'Upload & Manajemen Media')

@section('content')
    <div class="row g-4">
        <!-- Upload Section -->
        <div class="col-md-4">
            <div class="medizen-card-minimal">
                <div class="card-header border-bottom bg-slate-50 p-3">
                    <h6 class="mb-0 fw-bold text-slate-700 uppercase" style="font-size: 11px; letter-spacing: 1px;">
                        UPLOAD {{ $gallery->type === 'photo' ? 'IMAGE' : 'VIDEO' }} TO [{{ $gallery->name }}]
                    </h6>
                </div>
                <form action="{{ route('master.galleries.items.store', $gallery) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="p-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-slate-700 uppercase" style="font-size: 10px;">JUDUL /
                                CAPTION</label>
                            <input type="text" name="title" class="form-control medizen-input-minimal"
                                placeholder="Opsional...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-slate-700 uppercase" style="font-size: 10px;">PILIH
                                FILE</label>
                            <input type="file" name="file" class="form-control medizen-input-minimal" required>
                            <div class="form-text mt-1" style="font-size: 10px;">
                                @if($gallery->type === 'photo')
                                    Format: JPG, PNG, WEBP. Max 5MB.
                                @else
                                    Format: MP4, MOV, AVI. Max 50MB.
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="btn btn-emerald medizen-btn-minimal w-100">UPLOAD SEKARANG</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Media List Section -->
        <div class="col-md-8">
            <div class="medizen-card-minimal">
                <div class="card-header border-bottom bg-slate-50 p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-slate-700 uppercase" style="font-size: 11px; letter-spacing: 1px;">
                        MEDIA LIST ({{ $gallery->items()->count() }})
                    </h6>
                    <a href="{{ route('master.galleries.index') }}"
                        class="btn btn-outline-secondary medizen-btn-minimal py-1 px-2" style="font-size: 10px;">KEMBALI KE
                        LIST</a>
                </div>

                <div class="p-3">
                    <div class="row g-2">
                        @forelse($gallery->items as $item)
                            <div class="col-md-4 col-sm-6">
                                <div class="border p-2 bg-slate-50 position-relative group-hover">
                                    <div class="ratio ratio-16x9 bg-dark overflow-hidden mb-2">
                                        @if($gallery->type === 'photo')
                                            <img src="{{ asset('storage/' . $item->file_path) }}"
                                                class="object-fit-cover w-100 h-100" alt="{{ $item->title }}">
                                        @else
                                            <div
                                                class="d-flex flex-column align-items-center justify-content-center text-white small">
                                                <i data-feather="video" class="mb-1" style="width:16px;"></i>
                                                VIDEO FILE
                                            </div>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-truncate small fw-bold text-slate-700 pe-2" style="font-size: 10px;"
                                            title="{{ $item->title ?? 'Tanpa Judul' }}">
                                            {{ $item->title ?? 'Tanpa Judul' }}
                                        </div>
                                        <form action="{{ route('master.galleries.items.destroy', $item) }}" method="POST"
                                            class="swal-confirm" data-swal-title="Hapus Media?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Hapus">
                                                <i data-feather="trash-2" style="width: 12px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5 text-muted small uppercase">
                                BELUM ADA MEDIA TERUPLOAD PADA GALERI INI.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection