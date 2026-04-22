@extends('layouts.app')
@section('title', isset($radiographer) ? 'Edit Radiografer' : 'Tambah Radiografer')
@section('page-title', 'Formulir Tim Radiologi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="medizen-card-minimal">
                <div class="p-3 border-bottom bg-light-soft">
                    <h6 class="mb-0 fw-bold text-slate-800 uppercase" style="font-size: 13px; letter-spacing: 1px;">
                        {{ isset($radiographer) ? 'EDIT TECHNOLOGIST PROFILE' : 'REGISTER NEW TECHNOLOGIST' }}
                    </h6>
                </div>
                <div class="p-4">
                    <form method="POST"
                        action="{{ isset($radiographer) ? route('master.radiographers.update', $radiographer) : route('master.radiographers.store') }}">
                        @csrf
                        @if(isset($radiographer)) @method('PUT') @endif

                        <div class="mb-3">
                            <label class="medizen-label-minimal">STAFF FULL NAME <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                class="form-control medizen-input-minimal @error('name') is-invalid @enderror"
                                value="{{ old('name', $radiographer->name ?? '') }}" required
                                placeholder="e.g. Rad Tech Jane Doe">
                            @error('name') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">LICENSE ID (NIK)</label>
                                <input type="text" name="nik" class="form-control medizen-input-minimal"
                                    value="{{ old('nik', $radiographer->nik ?? '') }}" placeholder="16-digit National ID">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">PHONE / CONTACT</label>
                                <input type="text" name="phone" class="form-control medizen-input-minimal"
                                    value="{{ old('phone', $radiographer->phone ?? '') }}" placeholder="08xx xxxx xxxx">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="medizen-label-minimal">EMAIL ADDRESS</label>
                            <input type="email" name="email" class="form-control medizen-input-minimal"
                                value="{{ old('email', $radiographer->email ?? '') }}" placeholder="staff@medizen.com">
                        </div>

                        @if(isset($radiographer))
                            <div class="mb-4">
                                <div class="form-check form-switch custom-switch-emerald">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $radiographer->is_active) ? 'checked' : '' }} id="is_active">
                                    <label class="form-check-label small fw-bold text-slate-600" for="is_active">ACTIVE
                                        STATUS</label>
                                </div>
                            </div>
                        @endif

                        <div class="pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                                <i data-feather="save" class="me-1" style="width: 14px;"></i> SAVE STAFF DATA
                            </button>
                            <a href="{{ route('master.radiographers.index') }}"
                                class="btn btn-secondary medizen-btn-minimal">CANCEL</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection