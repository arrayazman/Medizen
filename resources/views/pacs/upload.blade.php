@extends('layouts.app')
@section('title', 'Upload Image ke DICOM PACS')
@section('page-title', 'Form Upload & Konversi Gambar')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 12px">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="mb-0 fw-bold text-primary"><i data-feather="upload-cloud" class="me-2 text-primary"
                            style="width: 20px; height: 20px;"></i>Konversi Manual / Eksternal</h5>
                    <p class="text-muted small mb-0 mt-1">Gunakan form ini untuk merubah gambar biasa (JPG/PNG) menjadi file
                        DICOM medis dan ditransfer ke PACS PACS.</p>
                </div>
                <div class="card-body pt-2">
                    <form action="{{ route('pacs.store-upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @if($orderInfo)
                        <div class="alert alert-{{ $orderInfo['_source'] === 'RIS' ? 'success' : 'info' }} rounded-0 py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.75rem; border-left: 4px solid {{ $orderInfo['_source'] === 'RIS' ? '#10b981' : '#0ea5e9' }};">
                            <i data-feather="{{ $orderInfo['_source'] === 'RIS' ? 'check-circle' : 'database' }}" style="width: 14px; min-width: 14px;"></i>
                            <div>
                                Data pasien otomatis diisi dari <strong>{{ $orderInfo['_source'] === 'RIS' ? 'Database RIS Lokal' : 'Database SIMRS' }}</strong>
                                &mdash; AccessionNumber: <strong>{{ $orderInfo['AccessionNumber'] }}</strong>.
                                Periksa kembali sebelum mengupload.
                            </div>
                        </div>

                        {{-- Clinical Context Panel (SIMRS only) --}}
                        @if(!empty($orderInfo['Items']) || !empty($orderInfo['DiagnosaKlinis']))
                        <div class="border rounded-0 bg-light mb-3" style="font-size: 0.72rem;">
                            <div class="px-3 py-2 bg-dark text-white fw-bold d-flex justify-content-between align-items-center" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                <span><i data-feather="clipboard" style="width: 11px;"></i> KONTEKS KLINIS DARI SIMRS</span>
                                @if(!empty($orderInfo['TglPermintaan']))
                                <span class="text-muted fw-normal">{{ \Carbon\Carbon::parse($orderInfo['TglPermintaan'])->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            <div class="row g-0">
                                {{-- Daftar Pemeriksaan --}}
                                @if(!empty($orderInfo['Items']))
                                <div class="col-md-6 border-end p-3">
                                    <div class="fw-bold text-muted mb-2" style="font-size: 0.6rem; letter-spacing: 0.5px;">DAFTAR PEMERIKSAAN</div>
                                    @foreach($orderInfo['Items'] as $item)
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                        <span class="fw-bold text-dark">{{ $item['nm_perawatan'] ?? '-' }}</span>
                                        <span class="badge bg-secondary rounded-0 ms-2" style="font-size: 0.55rem;">{{ $item['kd_jenis_prw'] ?? '' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Diagnosa & Info Klinis --}}
                                <div class="col-md-{{ !empty($orderInfo['Items']) ? '6' : '12' }} p-3">
                                    <div class="fw-bold text-muted mb-2" style="font-size: 0.6rem; letter-spacing: 0.5px;">DIAGNOSA & INFO KLINIS</div>
                                    @if(!empty($orderInfo['DiagnosaKlinis']))
                                    <div class="mb-2">
                                        <div class="text-muted" style="font-size: 0.6rem;">DIAGNOSA:</div>
                                        <div class="fw-bold">{{ $orderInfo['DiagnosaKlinis'] }}</div>
                                    </div>
                                    @endif
                                    @if(!empty($orderInfo['InfoTambahan']))
                                    <div>
                                        <div class="text-muted" style="font-size: 0.6rem;">INFO TAMBAHAN:</div>
                                        <div>{{ $orderInfo['InfoTambahan'] }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold mb-1">Nomor RM (PatientID) <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="PatientID" class="form-control" placeholder="Contoh: RM-123456"
                                    value="{{ old('PatientID', $orderInfo['PatientID'] ?? '') }}" required>
                                @error('PatientID') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold mb-1">Nama Pasien <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="PatientName" class="form-control" placeholder="John^Doe"
                                    value="{{ old('PatientName', $orderInfo['PatientName'] ?? '') }}" required>
                                @error('PatientName') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold mb-1">Tanggal Lahir (YYYYMMDD)</label>
                                <input type="text" name="PatientBirthDate" class="form-control"
                                    placeholder="Contoh: 19900130"
                                    value="{{ old('PatientBirthDate', $orderInfo['PatientBirthDate'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold mb-1">Jenis Kelamin</label>
                                <select name="PatientSex" class="form-select">
                                    <option value="O" {{ (old('PatientSex', $orderInfo['PatientSex'] ?? '') == 'O' || empty(old('PatientSex', $orderInfo['PatientSex'] ?? ''))) ? 'selected' : '' }}>
                                        Lainnya / O</option>
                                    <option value="M" {{ (old('PatientSex', $orderInfo['PatientSex'] ?? '') == 'M' || old('PatientSex', $orderInfo['PatientSex'] ?? '') == 'L') ? 'selected' : '' }}>
                                        Laki-laki (M)</option>
                                    <option value="F" {{ (old('PatientSex', $orderInfo['PatientSex'] ?? '') == 'F' || old('PatientSex', $orderInfo['PatientSex'] ?? '') == 'P') ? 'selected' : '' }}>
                                        Perempuan (F)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold mb-1">Modalitas</label>
                                <input type="text" name="Modality" class="form-control text-uppercase"
                                    placeholder="Contoh: US, CR, XC" maxlength="2"
                                    value="{{ old('Modality', $orderInfo['Modality'] ?? 'OT') }}">
                                @error('Modality') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold mb-1">Accession Number / No. Order</label>
                                <input type="text" name="AccessionNumber" class="form-control" placeholder="Contoh: ACC-001"
                                    value="{{ old('AccessionNumber', $orderInfo['AccessionNumber'] ?? '') }}">
                                <small class="text-muted" style="font-size:0.7rem">Berfungsi agar terhubung dengan pasien
                                    RIS sistem</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold mb-1">Deskripsi Studi/Pemeriksaan</label>
                                <input type="text" name="StudyDescription" class="form-control"
                                    placeholder="Contoh: USG Abdomen"
                                    value="{{ old('StudyDescription', $orderInfo['StudyDescription'] ?? '') }}">
                            </div>

                            <div class="col-12 mt-4">
                                <div class="p-4 border rounded bg-light text-center"
                                    style="border-style: dashed !important; border-width: 2px !important; border-color:#cbd5e1 !important">
                                    <i data-feather="image" style="width: 40px; height: 40px; color:#94a3b8"
                                        class="mb-2"></i>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold d-block">Pilih File Gambar (JPG/PNG) <span
                                                class="text-danger">*</span></label>
                                        <input type="file" name="dicom_images[]" id="dicomImages"
                                            class="form-control d-inline-block w-auto"
                                            accept="image/jpeg,image/png,image/jpg" multiple required>
                                    </div>
                                    <small class="text-muted d-block">Bisa memilih lebih dari satu gambar sekaligus (Max per
                                        file: 5MB)</small>
                                    @error('dicom_images') <small class="text-danger d-block mt-1">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <!-- Preview Area -->
                            <div class="col-12 mt-3" id="previewArea" style="display: none;">
                                <label class="form-label text-muted small fw-bold mb-2">Pratinjau Gambar:</label>
                                <div class="row g-2" id="imagePreviewContainer"></div>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <a href="{{ route('pacs.studies') }}" class="btn btn-light border px-4">Batal</a>
                                <button type="submit" class="btn btn-primary px-4 ms-2 shadow-sm" id="btnUpload"
                                    onclick="$(this).html('<span class=\'spinner-border spinner-border-sm\'></span> Sedang Upload...').addClass('disabled'); $(this).closest('form').submit();">
                                    <i data-feather="upload-cloud" style="width: 16px; height: 16px" class="me-1"></i> Mulai
                                    Konversi ke DICOM
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('dicomImages').addEventListener('change', function (event) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            const previewArea = document.getElementById('previewArea');
            previewContainer.innerHTML = '';

            const files = event.target.files;
            if (files.length > 0) {
                previewArea.style.display = 'block';
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (!file.type.match('image.*')) continue;

                    const reader = new FileReader();
                    reader.onload = (function (theFile) {
                        return function (e) {
                            const colBox = document.createElement('div');
                            colBox.className = 'col-4 col-md-3 col-lg-2';
                            colBox.innerHTML = `
                                <div class="card shadow-sm h-100">
                                    <img src="${e.target.result}" class="card-img-top" style="height:80px; object-fit:cover;">
                                    <div class="card-body p-1 text-center bg-light">
                                        <small class="text-truncate d-block" style="font-size: 0.65rem;" title="${escape(theFile.name)}">${escape(theFile.name)}</small>
                                    </div>
                                </div>
                            `;
                            previewContainer.appendChild(colBox);
                        };
                    })(file);
                    reader.readAsDataURL(file);
                }
            } else {
                previewArea.style.display = 'none';
            }
        });
    </script>
@endpush
