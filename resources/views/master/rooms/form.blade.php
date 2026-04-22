@extends('layouts.app')
@section('title', isset($room) ? 'Edit Ruangan' : 'Tambah Ruangan')
@section('page-title', 'Konfigurasi Fasilitas')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="medizen-card-minimal">
                <div class="p-3 border-bottom bg-light-soft">
                    <h6 class="mb-0 fw-bold text-slate-800 uppercase" style="font-size: 13px; letter-spacing: 1px;">
                        {{ isset($room) ? 'EDIT ROOM CONFIGURATION' : 'REGISTER NEW FACILITY ROOM' }}
                    </h6>
                </div>
                <div class="p-4">
                    <form method="POST"
                        action="{{ isset($room) ? route('master.rooms.update', $room) : route('master.rooms.store') }}">
                        @csrf
                        @if(isset($room)) @method('PUT') @endif

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="medizen-label-minimal">ROOM CODE <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control medizen-input-minimal"
                                    value="{{ old('code', $room->code ?? '') }}" required placeholder="e.g. RM-01">
                            </div>
                            <div class="col-md-8">
                                <label class="medizen-label-minimal">ROOM NAME <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control medizen-input-minimal"
                                    value="{{ old('name', $room->name ?? '') }}" required
                                    placeholder="e.g. CT Scan Room Main">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">PRIMARY MODALITY</label>
                                <select name="modality_id" class="form-select medizen-input-minimal">
                                    <option value="">- AUTO ASSIGN -</option>
                                    @foreach($modalities as $m)
                                        <option value="{{ $m->id }}" {{ old('modality_id', $room->modality_id ?? '') == $m->id ? 'selected' : '' }}>
                                            {{ $m->code }} - {{ $m->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">FLOOR LEVEL</label>
                                <input type="text" name="floor" class="form-control medizen-input-minimal"
                                    value="{{ old('floor', $room->floor ?? '') }}" placeholder="e.g. 1st Floor">
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                                <i data-feather="save" class="me-1" style="width: 14px;"></i> SAVE ROOM DATA
                            </button>
                            <a href="{{ route('master.rooms.index') }}"
                                class="btn btn-secondary medizen-btn-minimal">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection