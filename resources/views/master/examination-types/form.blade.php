@extends('layouts.app')
@section('title', isset($examinationType) ? 'Edit Jenis Pemeriksaan' : 'Tambah Jenis Pemeriksaan')
@section('page-title', 'Konfigurasi Tarif & Katalog Jasa')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="medizen-card-minimal">
                <div class="card-body p-4">
                    <div class="medizen-form-group-title mb-4">
                        <i data-feather="settings"></i>
                        {{ isset($examinationType) ? 'EDIT RADIOLOGY PROCEDURE' : 'REGISTER NEW MEDICAL PROCEDURE' }}
                    </div>
                    <form method="POST"
                        action="{{ isset($examinationType) ? route('master.examination-types.update', $examinationType) : route('master.examination-types.store') }}">
                        @csrf
                        @if(isset($examinationType)) @method('PUT') @endif

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">MODALITY CATEGORY <span
                                        class="text-danger">*</span></label>
                                <select name="modality_id" class="medizen-input-minimal" required>
                                    <option value="">- SELECT MODALITY -</option>
                                    @foreach($modalities as $m)
                                        <option value="{{ $m->id }}" {{ old('modality_id', $examinationType->modality_id ?? '') == $m->id ? 'selected' : '' }}>
                                            {{ $m->code }} - {{ $m->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="medizen-label-minimal">ESTIMATED DURATION (MIN) <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="duration_minutes" class="medizen-input-minimal"
                                    value="{{ old('duration_minutes', $examinationType->duration_minutes ?? 15) }}" required
                                    min="1">
                            </div>
                        </div>

                        <div class="medizen-card-minimal mb-4 bg-light-soft">
                            <div class="card-body p-3">
                                <div class="medizen-form-group-title mb-3"
                                    style="font-size: 11px; border-bottom: 1px solid #e2e8f0;">
                                    <i data-feather="dollar-sign"></i> :: RADIOLOGY PRICING COMPONENTS ::
                                </div>

                                <div class="row g-3">
                                    <!-- Left Column -->
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">PROCEDURE CODE</label>
                                            <input type="text" name="code" class="medizen-input-minimal fw-bold"
                                                value="{{ old('code', $examinationType->code ?? '') }}" required
                                                placeholder="e.g. CT-THORAX-01">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">J.S. HOSPITAL (IDR)</label>
                                            <input type="number" name="js_rs" class="medizen-input-minimal tarif-input"
                                                value="{{ old('js_rs', isset($examinationType) ? intval($examinationType->js_rs) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">PAKET BHP (IDR)</label>
                                            <input type="number" name="paket_bhp" class="medizen-input-minimal tarif-input"
                                                value="{{ old('paket_bhp', isset($examinationType) ? intval($examinationType->paket_bhp) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">J.M. PHYSICIAN (IDR)</label>
                                            <input type="number" name="jm_dokter" class="medizen-input-minimal tarif-input"
                                                value="{{ old('jm_dokter', isset($examinationType) ? intval($examinationType->jm_dokter) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">J.M. STAFF/TECH (IDR)</label>
                                            <input type="number" name="jm_petugas" class="medizen-input-minimal tarif-input"
                                                value="{{ old('jm_petugas', isset($examinationType) ? intval($examinationType->jm_petugas) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">J.M. REFERRER (IDR)</label>
                                            <input type="number" name="jm_perujuk" class="medizen-input-minimal tarif-input"
                                                value="{{ old('jm_perujuk', isset($examinationType) ? intval($examinationType->jm_perujuk) : 0) }}"
                                                min="0">
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">PROCEDURE NAME</label>
                                            <input type="text" name="name"
                                                class="form-control medizen-input-minimal fw-bold"
                                                value="{{ old('name', $examinationType->name ?? '') }}" required
                                                placeholder="e.g. CT THORAX WITH CONTRAST">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">K.S.O. (IDR)</label>
                                            <input type="number" name="kso" class="medizen-input-minimal tarif-input"
                                                value="{{ old('kso', isset($examinationType) ? intval($examinationType->kso) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-2">
                                            <label class="medizen-label-minimal">MANAGEMENT FEE (IDR)</label>
                                            <input type="number" name="manajemen" class="medizen-input-minimal tarif-input"
                                                value="{{ old('manajemen', isset($examinationType) ? intval($examinationType->manajemen) : 0) }}"
                                                min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="medizen-label-minimal text-emerald">TOTAL RADIOLOGY PRICE (AUTO
                                                CALC)</label>
                                            <input type="number" name="price" id="total_biaya"
                                                class="medizen-input-minimal bg-white fw-bold border-emerald"
                                                value="{{ old('price', isset($examinationType) ? intval($examinationType->price) : 0) }}"
                                                required readonly>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-12">
                                                <label class="medizen-label-minimal">PROCEDURE CLASS / CATEGORY</label>
                                                <select name="kelas" class="medizen-input-minimal">
                                                    <option value="-">- SELECT CLASS -</option>
                                                    @foreach(['Kelas 1', 'Kelas 2', 'Kelas 3', 'VIP', 'VVIP'] as $kls)
                                                        <option value="{{ $kls }}" {{ old('kelas', $examinationType->kelas ?? '') == $kls ? 'selected' : '' }}>{{ $kls }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="medizen-label-minimal">ADDITIONAL NOTES / DESCRIPTION</label>
                                <textarea name="description" class="medizen-input-minimal" rows="2"
                                    placeholder="Clinical notes or specific equipment requirements...">{{ old('description', $examinationType->description ?? '') }}</textarea>
                            </div>

                            <div class="pt-3 border-top d-flex gap-2">
                                <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                                    <i data-feather="save" class="me-1" style="width: 14px;"></i> SAVE PROCEDURE
                                </button>
                                <a href="{{ route('master.examination-types.index') }}"
                                    class="btn btn-secondary medizen-btn-minimal">CANCEL</a>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                function calculateTotal() {
                    let total = 0;
                    $('.tarif-input').each(function () {
                        let val = parseFloat($(this).val()) || 0;
                        total += val;
                    });
                    $('#total_biaya').val(total);
                }
                $('.tarif-input').on('input', function () {
                    calculateTotal();
                });
            });
        </script>
    @endpush
@endsection