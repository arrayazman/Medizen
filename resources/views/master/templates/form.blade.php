@extends('layouts.app')
@section('title', isset($template) ? 'Edit Template Hasil' : 'Tambah Template Hasil')
@section('page-title', 'Konfigurasi Template Laporan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="medizen-card-minimal">
                <div class="p-3 border-bottom bg-light-soft">
                    <h6 class="mb-0 fw-bold text-slate-800 uppercase" style="font-size: 13px; letter-spacing: 1px;">
                        {{ isset($template) ? 'MODIFY REPORT TEMPLATE' : 'DEFINE NEW REPORT RESOURCE' }}
                    </h6>
                </div>
                <div class="p-4">
                    <form
                        action="{{ isset($template) ? route('master.templates.update', $template) : route('master.templates.store') }}"
                        method="POST">
                        @csrf
                        @if(isset($template)) @method('PUT') @endif

                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="medizen-label-minimal">REFERENCE ID <span class="text-danger">*</span></label>
                                <input type="text" name="template_number" class="form-control medizen-input-minimal"
                                    value="{{ old('template_number', $template->template_number ?? '') }}" required
                                    placeholder="e.g. TMP-01">
                                @error('template_number')
                                    <div class="text-danger mt-1 small font-monospace">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-9">
                                <label class="medizen-label-minimal">EXAMINATION CATEGORY NAME <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="examination_name" class="form-control medizen-input-minimal"
                                    value="{{ old('examination_name', $template->examination_name ?? '') }}" required
                                    placeholder="Enter examination name...">
                                @error('examination_name')
                                    <div class="text-danger mt-1 small font-monospace">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="medizen-label-minimal">EXPERTISE DRAFT TEMPLATE <span
                                        class="text-danger">*</span></label>
                                <span class="medizen-indicator active">ACTIVE RESOURCE</span>
                            </div>
                            <textarea name="expertise" class="form-control medizen-input-minimal font-monospace" rows="15"
                                required style="font-size: 13px !important;"
                                placeholder="Write expertise template here...">{{ old('expertise', $template->expertise ?? '') }}</textarea>
                        </div>

                        <div class="pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                                <i data-feather="check" class="me-1" style="width: 14px;"></i> COMMIT CHANGES
                            </button>
                            <a href="{{ route('master.templates.index') }}"
                                class="btn btn-secondary medizen-btn-minimal">DISCARD</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection