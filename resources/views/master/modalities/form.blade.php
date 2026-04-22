@extends('layouts.app')
@section('title', isset($modality) ? 'Edit Modalitas' : 'Tambah Modalitas')
@section('page-title', 'Formulir Inventaris Modalitas')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="medizen-card-minimal">
                <div class="card-body p-4">
                    <div class="medizen-form-group-title mb-4">
                        <i data-feather="monitor"></i>
                        {{ isset($modality) ? 'EDIT MODALITY CONFIG' : 'REGISTER NEW IMAGING UNIT' }}
                    </div>
                    <form method="POST"
                        action="{{ isset($modality) ? route('master.modalities.update', $modality) : route('master.modalities.store') }}">
                        @csrf
                        @if(isset($modality)) @method('PUT') @endif

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="medizen-label-minimal">MODALITY CODE <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="code"
                                    class="medizen-input-minimal @error('code') is-invalid @enderror"
                                    value="{{ old('code', $modality->code ?? '') }}" required placeholder="e.g. CT01">
                                @error('code') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="medizen-label-minimal">MODALITY NAME <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="medizen-input-minimal"
                                    value="{{ old('name', $modality->name ?? '') }}" required
                                    placeholder="e.g. CT Scan Multi Slice">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="medizen-label-minimal">AE TITLE (DICOM)</label>
                            <input type="text" name="ae_title" class="medizen-input-minimal font-monospace"
                                value="{{ old('ae_title', $modality->ae_title ?? '') }}" placeholder="AETITLE">
                        </div>

                        <div class="mb-3">
                            <label class="medizen-label-minimal">DESCRIPTION / SPECIFICATIONS</label>
                            <textarea name="description" class="medizen-input-minimal" rows="3"
                                placeholder="Enter hardware specs or notes...">{{ old('description', $modality->description ?? '') }}</textarea>
                        </div>

                        @if(isset($modality))
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $modality->is_active) ? 'checked' : '' }} id="is_active">
                                    <label class="medizen-label-minimal ms-2" for="is_active" style="cursor:pointer;">ACTIVE
                                        STATUS</label>
                                </div>
                            </div>
                        @endif

                        <div class="pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                                <i data-feather="save" class="me-1" style="width: 14px;"></i> SAVE MODALITY
                            </button>
                            <a href="{{ route('master.modalities.index') }}"
                                class="btn btn-secondary medizen-btn-minimal">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection