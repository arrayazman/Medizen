@extends('layouts.app')
@section('title', 'Tambah Galeri')
@section('page-title', 'Konfigurasi Media Baru')

@section('content')
    <div class="medizen-card-minimal">
        <div class="card-header border-bottom bg-slate-50 p-3">
            <h6 class="mb-0 fw-bold text-slate-700 uppercase" style="font-size: 13px; letter-spacing: 1px;">
                CREATE NEW MEDIA GALLERY SETTING
            </h6>
        </div>

        <form action="{{ route('master.galleries.store') }}" method="POST">
            @csrf
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-slate-700 uppercase" style="font-size: 11px;">NAMA
                            GALERI</label>
                        <input type="text" name="name"
                            class="form-control medizen-input-minimal @error('name') is-invalid @enderror"
                            placeholder="Contoh: Galeri Edukasi X-Ray..." value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-slate-700 uppercase" style="font-size: 11px;">TIPE
                            MEDIA</label>
                        <div class="d-flex gap-4 p-2 bg-slate-50 border rounded mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typePhoto" value="photo"
                                    checked>
                                <label class="form-check-label fw-bold medizen-indicator p-0" for="typePhoto"
                                    style="cursor: pointer;">PHOTO SLIDESHOW</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeVideo" value="video">
                                <label class="form-check-label fw-bold medizen-indicator p-0" for="typeVideo"
                                    style="cursor: pointer;">VIDEO PLAYLIST</label>
                            </div>
                        </div>
                        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="p-3 border-top bg-slate-50 d-flex justify-content-end gap-2">
                <a href="{{ route('master.galleries.index') }}"
                    class="btn btn-outline-secondary medizen-btn-minimal">BATAL</a>
                <button type="submit" class="btn btn-emerald medizen-btn-minimal">SIMPAN GALERI</button>
            </div>
        </form>
    </div>
@endsection