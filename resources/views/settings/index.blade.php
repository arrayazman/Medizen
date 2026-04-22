@extends('layouts.app')
@section('title', 'Setting Instansi')
@section('page-title', 'Pengaturan Aplikasi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-11">

            @if(session('success'))
                <div class="alert bg-success-soft text-success border-0 shadow-sm alert-dismissible fade show d-flex align-items-center mb-4"
                    role="alert" style="border-radius: 4px;">
                    <i data-feather="check-circle" class="me-2" style="width: 18px; height: 18px;"></i>
                    <span class="small fw-semibold">{{ session('success') }}</span>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert" aria-label="Close"
                        style="font-size: 0.7rem;"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert bg-danger-soft text-danger border-0 shadow-sm alert-dismissible fade show d-flex align-items-center mb-4"
                    role="alert" style="border-radius: 4px;">
                    <i data-feather="alert-circle" class="me-2" style="width: 18px; height: 18px;"></i>
                    <div class="small fw-semibold">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert" aria-label="Close"
                        style="font-size: 0.7rem;"></button>
                </div>
            @endif

            <div class="card card-medizen border-0 mt-2">
                <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 d-flex align-items-center">
                    <div class="bg-primary-soft text-primary p-2 rounded-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px;">
                        <i data-feather="settings" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-slate-800" style="font-size: 0.95rem; letter-spacing: 0.5px;">Identitas
                            Sistem & Instansi</h6>
                        <div class="text-muted mt-1" style="font-size: 0.65rem;">PENGATURAN HEADER, FOOTER CETAKAN, DAN
                            INTEGRASI LAYANAN CLOUD</div>
                    </div>
                </div>

                <div class="card-body p-0 bg-light-soft pb-4">
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Section: Informasi Umum -->
                        <div class="bg-white p-4 mx-4 mt-4 shadow-sm"
                            style="border-radius: 6px; border: 1px solid rgba(0,0,0,0.03);">
                            <h6 class="fw-bold text-slate-700 mb-4 pb-2 border-bottom"
                                style="font-size: 0.8rem; letter-spacing: 1px;">INFORMASI UMUM</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">NAMA KLINIK / RUMAH SAKIT <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="hospital_name"
                                        class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-800"
                                        value="{{ old('hospital_name', $setting->hospital_name) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">KODE LISENSI PACS</label>
                                    <input type="text" name="pacs_license_code"
                                        class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none font-monospace fw-bold text-success"
                                        value="{{ old('pacs_license_code', $setting->pacs_license_code) }}"
                                        placeholder="Kosongkan jika tidak ada / LIC-PACS-12345">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">ALAMAT LENGKAP</label>
                                <textarea name="address" rows="2"
                                    class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-700"
                                    style="resize: none;">{{ old('address', $setting->address) }}</textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">TELEPON</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light-soft border-light text-muted"><i
                                                data-feather="phone" style="width: 12px; height: 12px;"></i></span>
                                        <input type="text" name="phone"
                                            class="form-control border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-700"
                                            value="{{ old('phone', $setting->phone) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">EMAIL</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light-soft border-light text-muted"><i
                                                data-feather="mail" style="width: 12px; height: 12px;"></i></span>
                                        <input type="email" name="email"
                                            class="form-control border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-700"
                                            value="{{ old('email', $setting->email) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">WEBSITE</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light-soft border-light text-muted"><i
                                                data-feather="globe" style="width: 12px; height: 12px;"></i></span>
                                        <input type="text" name="website"
                                            class="form-control border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-700"
                                            value="{{ old('website', $setting->website) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Media & Logo -->
                        <div class="bg-white p-4 mx-4 mt-4 shadow-sm"
                            style="border-radius: 6px; border: 1px solid rgba(0,0,0,0.03);">
                            <h6 class="fw-bold text-slate-700 mb-4 pb-2 border-bottom d-flex align-items-center"
                                style="font-size: 0.8rem; letter-spacing: 1px;">
                                <i data-feather="image" style="width: 14px; height: 14px;" class="me-2 text-emerald"></i>
                                MEDIA CETAK & LOGO
                            </h6>

                            <div class="row g-4">
                                <!-- Logo -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-light bg-light-soft shadow-none"
                                        style="border-radius: 4px;">
                                        <div class="card-body text-center p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label text-muted fw-bold mb-0"
                                                    style="font-size: 0.6rem; letter-spacing: 0.5px;">LOGO INSTANSI
                                                    (1:1)</label>
                                                @if($setting->logo_path)
                                                    <span
                                                        class="badge bg-success-soft text-success px-2 py-0 border border-success border-opacity-25"
                                                        style="border-radius: 2px; font-size: 0.55rem;">Aktif</span>
                                                @endif
                                            </div>
                                            <div class="mb-3 d-flex align-items-center justify-content-center bg-white border"
                                                style="min-height: 100px; border-style: dashed !important; border-radius: 4px;">
                                                @if($setting->logo_path)
                                                    <img src="{{ asset($setting->logo_path) }}" alt="Logo"
                                                        style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                                @else
                                                    <span class="text-muted" style="font-size: 0.65rem;">Belum ada logo</span>
                                                @endif
                                            </div>
                                            <input type="file" name="logo"
                                                class="form-control form-control-sm border-light shadow-none bg-white file-medizen"
                                                accept="image/png, image/jpeg" style="font-size: 0.65rem;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Watermark -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-light bg-light-soft shadow-none"
                                        style="border-radius: 4px;">
                                        <div class="card-body text-center p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label text-muted fw-bold mb-0"
                                                    style="font-size: 0.6rem; letter-spacing: 0.5px;">WATERMARK
                                                    DOKUMEN</label>
                                                @if($setting->watermark_path)
                                                    <span
                                                        class="badge bg-success-soft text-success px-2 py-0 border border-success border-opacity-25"
                                                        style="border-radius: 2px; font-size: 0.55rem;">Aktif</span>
                                                @endif
                                            </div>
                                            <div class="mb-3 d-flex align-items-center justify-content-center bg-white border"
                                                style="min-height: 100px; border-style: dashed !important; border-radius: 4px;">
                                                @if($setting->watermark_path)
                                                    <img src="{{ asset($setting->watermark_path) }}" alt="Watermark"
                                                        style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                                @else
                                                    <span class="text-muted" style="font-size: 0.65rem;">Belum ada
                                                        watermark</span>
                                                @endif
                                            </div>
                                            <input type="file" name="watermark"
                                                class="form-control form-control-sm border-light shadow-none bg-white file-medizen"
                                                accept="image/png, image/jpeg" style="font-size: 0.65rem;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="col-md-4">
                                    <div class="card h-100 border-light bg-light-soft shadow-none"
                                        style="border-radius: 4px;">
                                        <div class="card-body text-center p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label text-muted fw-bold mb-0"
                                                    style="font-size: 0.6rem; letter-spacing: 0.5px;">FOOTER
                                                    (MEMANJANG)</label>
                                                @if($setting->footer_path)
                                                    <span
                                                        class="badge bg-success-soft text-success px-2 py-0 border border-success border-opacity-25"
                                                        style="border-radius: 2px; font-size: 0.55rem;">Aktif</span>
                                                @endif
                                            </div>
                                            <div class="mb-3 d-flex align-items-center justify-content-center bg-white border"
                                                style="min-height: 100px; border-style: dashed !important; border-radius: 4px;">
                                                @if($setting->footer_path)
                                                    <img src="{{ asset($setting->footer_path) }}" alt="Footer"
                                                        style="max-height: 80px; max-width: 100%; object-fit: contain;">
                                                @else
                                                    <span class="text-muted" style="font-size: 0.65rem;">Belum ada banner</span>
                                                @endif
                                            </div>
                                            <input type="file" name="footer"
                                                class="form-control form-control-sm border-light shadow-none bg-white file-medizen"
                                                accept="image/png, image/jpeg" style="font-size: 0.65rem;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Display Settings -->
                        <div class="bg-white p-4 mx-4 mt-4 shadow-sm"
                            style="border-radius: 6px; border: 1px solid rgba(0,0,0,0.03);">
                            <h6 class="fw-bold text-slate-700 mb-4 pb-2 border-bottom d-flex align-items-center"
                                style="font-size: 0.8rem; letter-spacing: 1px;">
                                <i data-feather="monitor" style="width: 14px; height: 14px;" class="me-2 text-primary"></i>
                                PENGATURAN DISPLAY ANTREAN
                            </h6>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">PILIH GALERI AKTIF</label>
                                    <select name="display_gallery_id"
                                        class="form-select form-select-sm border-light bg-light-soft py-2 px-3 shadow-none text-slate-800 fw-bold">
                                        <option value="">-- DEFAULT (SLIDESHOW RADIOLOGI) --</option>
                                        @foreach($galleries as $g)
                                            <option value="{{ $g->id }}" {{ old('display_gallery_id', $setting->display_gallery_id) == $g->id ? 'selected' : '' }}>
                                                [{{ strtoupper($g->type) }}] {{ $g->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert bg-blue-soft border-0 p-2 mb-0 d-flex align-items-center"
                                        style="border-radius: 4px;">
                                        <i data-feather="info" class="text-primary me-2" style="width: 14px;"></i>
                                        <span class="text-primary fw-semibold" style="font-size: 0.65rem;">Galeri terpilih
                                            akan muncul otomatis di monitor antrean pusat.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">INSTRUKSI PELAYANAN (URUTAN NOMOR)</label>
                                
                                <div id="instruction-container">
                                    @php $instructions = $setting->display_instructions ?? ['Siapkan Nomor Pendaftaran & Identitas diri (KTP/SIM) Anda.', 'Masuk ke ruang sampling SETELAH nama Anda muncul di monitor utama.']; @endphp
                                    @foreach($instructions as $idx => $inst)
                                        <div class="input-group input-group-sm mb-2 instruction-row">
                                            <span class="input-group-text bg-light-soft border-light text-muted fw-bold" style="font-size: 10px; min-width: 30px;">{{ $idx + 1 }}</span>
                                            <input type="text" name="display_instructions[]"
                                                class="form-control border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-800"
                                                value="{{ $inst }}" placeholder="Masukkan instruksi...">
                                            <button type="button" class="btn btn-outline-danger border-light remove-instruction">
                                                <i data-feather="trash-2" style="width: 12px;"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <button type="button" id="add-instruction" class="btn btn-light-soft text-primary fw-bold p-1 mt-1 d-flex align-items-center" style="font-size: 10px; border-radius: 4px;">
                                    <i data-feather="plus-circle" class="me-1" style="width: 14px;"></i> TAMBAH BARIS INSTRUKSI
                                </button>
                            </div>

                            <div class="mt-3">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">TEKS BERJALAN (MARQUEE)</label>
                                <textarea name="display_marquee_text" rows="2"
                                    class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-700"
                                    placeholder="Masukkan teks pengumuman yang akan berjalan di bawah layar..."
                                    style="resize: none;">{{ old('display_marquee_text', $setting->display_marquee_text) }}</textarea>
                            </div>
                        </div>

                        @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const container = document.getElementById('instruction-container');
                                const addButton = document.getElementById('add-instruction');

                                function updateIndex() {
                                    container.querySelectorAll('.instruction-row').forEach((row, index) => {
                                        row.querySelector('.input-group-text').textContent = index + 1;
                                    });
                                }

                                addButton.addEventListener('click', function() {
                                    const row = document.createElement('div');
                                    row.className = 'input-group input-group-sm mb-2 instruction-row';
                                    row.innerHTML = `
                                        <span class="input-group-text bg-light-soft border-light text-muted fw-bold" style="font-size: 10px; min-width: 30px;"></span>
                                        <input type="text" name="display_instructions[]"
                                            class="form-control border-light bg-light-soft py-2 px-3 shadow-none fw-semibold text-slate-800"
                                            placeholder="Masukkan instruksi berikutnya...">
                                        <button type="button" class="btn btn-outline-danger border-light remove-instruction">
                                            <i data-feather="trash-2" style="width: 12px;"></i>
                                        </button>
                                    `;
                                    container.appendChild(row);
                                    feather.replace();
                                    updateIndex();
                                });

                                container.addEventListener('click', function(e) {
                                    if (e.target.closest('.remove-instruction')) {
                                        e.target.closest('.instruction-row').remove();
                                        updateIndex();
                                    }
                                });
                            });
                        </script>
                        @endpush

                        <!-- Section: SatuSehat -->
                        <div class="bg-white p-4 mx-4 mt-4 shadow-sm position-relative overflow-hidden border-start border-primary border-4"
                            style="border-radius: 6px; border-top: 1px solid rgba(0,0,0,0.03); border-bottom: 1px solid rgba(0,0,0,0.03); border-right: 1px solid rgba(0,0,0,0.03);">
                            <div class="position-absolute end-0 top-0 pt-3 pe-4 opacity-10 text-primary"
                                style="transform: scale(1.5);">
                                <i data-feather="cloud" style="width: 60px; height: 60px;"></i>
                            </div>

                            <h6 class="fw-bold text-primary mb-4 pb-2 border-bottom d-flex align-items-center"
                                style="font-size: 0.8rem; letter-spacing: 1px;">
                                <i data-feather="share-2" style="width: 14px; height: 14px;" class="me-2"></i>
                                INTEGRASI SATUSEHAT KEMENKES
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">ORGANIZATION ID</label>
                                    <input type="text" name="satusehat_organization_id"
                                        class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none font-monospace text-slate-700"
                                        value="{{ old('satusehat_organization_id', $setting->satusehat_organization_id) }}"
                                        placeholder="10002xxxx">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">ENVIRONMENT</label>
                                    <select name="satusehat_env"
                                        class="form-select form-select-sm border-light bg-light-soft py-2 px-3 shadow-none text-slate-700 fw-bold">
                                        <option value="sandbox" {{ old('satusehat_env', $setting->satusehat_env) == 'sandbox' ? 'selected' : '' }}>SANDBOX (TESTING)</option>
                                        <option value="production" {{ old('satusehat_env', $setting->satusehat_env) == 'production' ? 'selected' : '' }}>PRODUCTION (LIVE)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">CLIENT ID</label>
                                    <input type="text" name="satusehat_client_id"
                                        class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none font-monospace text-slate-700"
                                        value="{{ old('satusehat_client_id', $setting->satusehat_client_id) }}"
                                        placeholder="************">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted fw-bold mb-1"
                                        style="font-size: 0.65rem; letter-spacing: 0.5px;">CLIENT SECRET</label>
                                    <input type="password" name="satusehat_client_secret"
                                        class="form-control form-control-sm border-light bg-light-soft py-2 px-3 shadow-none font-monospace text-slate-700"
                                        value="{{ old('satusehat_client_secret', $setting->satusehat_client_secret) }}"
                                        placeholder="************">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Area -->
                        <div class="mx-4 mt-4 mb-2 bg-white p-3 shadow-sm d-flex justify-content-between align-items-center"
                            style="border-radius: 6px; border: 1px solid rgba(0,0,0,0.03);">
                            <div class="text-muted small px-2 fw-semibold" style="font-size: 0.7rem;">
                                Pastikan mengisi kolom wajib. Klik tombol untuk memproses perubahan.
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm px-4 py-2 shadow-sm fw-bold"
                                style="font-size: 0.75rem; border-radius: 4px;">
                                <i data-feather="save" style="width: 14px; height: 14px;" class="me-2"></i> SIMPAN
                                PENGATURAN
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection