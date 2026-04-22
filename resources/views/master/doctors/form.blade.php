@extends('layouts.app')
@section('title', isset($doctor) ? 'Edit Dokter' : 'Tambah Dokter')
@section('page-title', 'Formulir Staff Medis')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="medizen-card-minimal">
            <div class="card-body p-4">
                <div class="medizen-form-group-title mb-4">
                    <i data-feather="user"></i>
                    {{ isset($doctor) ? 'EDIT PHYSICIAN DETAILS' : 'REGISTER NEW PHYSICIAN' }}
                </div>
                <form method="POST" action="{{ isset($doctor) ? route('master.doctors.update', $doctor) : route('master.doctors.store') }}">
                    @csrf
                    @if(isset($doctor)) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="medizen-label-minimal">FULL NAME <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="medizen-input-minimal @error('name') is-invalid @enderror"
                            value="{{ old('name', $doctor->name ?? '') }}" required placeholder="e.g. Dr. John Doe, Sp.Rad">
                        @error('name') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="medizen-label-minimal">SPECIALIZATION</label>
                        <input type="text" name="specialization" class="medizen-input-minimal"
                            value="{{ old('specialization', $doctor->specialization ?? '') }}" placeholder="e.g. Radiology Specialist">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="medizen-label-minimal">SIP NUMBER (LICENSE)</label>
                            <input type="text" name="sip_number" class="medizen-input-minimal @error('sip_number') is-invalid @enderror"
                                value="{{ old('sip_number', $doctor->sip_number ?? '') }}" placeholder="000/SIP/XXX/202X">
                            @error('sip_number') <div class="invalid-feedback small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="medizen-label-minimal">PHONE / CONTACT</label>
                            <input type="text" name="phone" class="medizen-input-minimal"
                                value="{{ old('phone', $doctor->phone ?? '') }}" placeholder="+62 8xx xxxx xxxx">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="medizen-label-minimal">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="medizen-input-minimal"
                            value="{{ old('email', $doctor->email ?? '') }}" placeholder="physician@medizen.com">
                    </div>

                    @if(isset($doctor))
                    <div class="mb-4">
                         <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                {{ old('is_active', $doctor->is_active) ? 'checked' : '' }} id="is_active">
                            <label class="medizen-label-minimal ms-2" for="is_active" style="cursor:pointer;">ACTIVE STATUS</label>
                        </div>
                    </div>
                    @endif

                    <div class="pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                            <i data-feather="save" class="me-1" style="width: 14px;"></i> SAVE DATA
                        </button>
                        <a href="{{ route('master.doctors.index') }}" class="btn btn-secondary medizen-btn-minimal">CANCEL</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
