@extends('layouts.app')

@section('page-title', 'Hasil Radiologi (SIMRS)')

@section('content')
    <div class="card card-medizen rounded-0 border-0 shadow-none">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Hasil Radiologi (SIMRS)</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">REALTIME COMPLETED EXAMINATIONS FROM SIMRS KHANZA</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i data-feather="filter" class="me-2" style="width: 14px;"></i> ADVANCED FILTER
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Integrated Filter Bar -->
            <div class="collapse {{ request('start_date') ? 'show' : '' }} p-1 bg-light-soft border-bottom" id="filterCollapse">
                <form action="{{ route('simrs.hasil') }}" method="GET" class="p-2">
                    <div class="row g-1">
                        <div class="col-md-3 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AWAL</label>
                            <input type="date" name="start_date" class="form-control form-control-sm rounded-0" value="{{ $startDate }}" style="font-size: 0.6rem; height: 28px;">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AKHIR</label>
                            <input type="date" name="end_date" class="form-control form-control-sm rounded-0" value="{{ $endDate }}" style="font-size: 0.6rem; height: 28px;">
                        </div>
                        <div class="col-md-4 col-12">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">CARI PASIEN / RM</label>
                            <div class="position-relative">
                                <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted" style="width: 10px;"></i>
                                <input type="text" name="search" class="form-control form-control-sm ps-4 rounded-0" placeholder="Search..." value="{{ $search }}" style="font-size: 0.6rem; height: 28px;">
                            </div>
                        </div>
                        <div class="col-md-2 col-12 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 flex-fill" style="font-size: 0.6rem; height: 28px;">
                                <i data-feather="check" class="me-1" style="width: 10px;"></i> APPLY
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 small ps-3">Patient & Record</th>
                            <th class="py-2 small text-center">Examination Time</th>
                            <th class="py-2 small text-center">Clinician / Personnel</th>
                            <th class="py-2 small text-center">Status</th>
                            <th class="py-2 small text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($results as $result)
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="fw-bold text-slate-800 small">{{ strtoupper($result->nm_pasien) }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">RM: <span class="privacy-mask">{{ $result->no_rkm_medis }}</span> | #{{ $result->no_rawat }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    <div class="fw-bold text-slate-700" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($result->tgl_periksa)->format('d/m/Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;"><i data-feather="clock" class="me-1" style="width: 10px;"></i> {{ $result->jam }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    <div class="small fw-bold text-dark text-truncate mx-auto" style="max-width: 150px; font-size: 0.65rem;"><span class="privacy-mask">{{ $result->dokter_radiologi }}</span></div>
                                    <div class="text-muted small" style="font-size: 0.6rem;">PETUGAS: <span class="privacy-mask">{{ $result->nama_petugas }}</span></div>
                                </td>
                                <td class="py-2 text-center">
                                    @if($result->hasil)
                                        <span class="badge-modern bg-emerald text-white px-2 mb-1" style="transform: scale(0.85);">REPORTED</span>
                                    @else
                                        <span class="badge-modern bg-light text-muted border px-2 mb-1" style="transform: scale(0.85);">PENDING</span>
                                    @endif
                                    <div class="mt-1">
                                        <span class="x-small {{ $result->gambar_count > 0 ? 'text-emerald fw-bold' : 'text-muted' }}" style="font-size: 0.55rem;">
                                            <i data-feather="image" style="width: 10px;"></i> {{ $result->gambar_count }} IMGS
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2 text-end pe-3">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <button class="btn btn-light rounded-0 px-2 py-1 x-small fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetail-{{ $loop->index }}">DETAIL</button>
                                        @if($result->noorder)
                                            <button class="btn btn-emerald rounded-0 px-2 py-1 x-small fw-bold text-white border-0" onclick='openExpertiseModal("{{ $result->noorder }}", "{{ $result->nm_pasien }}", "{{ $result->no_rawat }}", {!! json_encode($result->hasil) !!}, "{{ $result->tgl_periksa }}", "{{ $result->jam }}")'>EDIT</button>
                                        @endif
                                    </div>

                                    <!-- Detail Modal (Medizen Flat Clean) -->
                                    <div class="modal fade" id="modalDetail-{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content border-0 rounded-0 shadow-none">
                                                
                                                <div class="modal-header border-0 bg-dark text-white py-2 rounded-0 align-items-center">
                                                    <div>
                                                        <h6 class="modal-title fw-bold mb-0">DETAIL EXPERTISE RADIOLOGI</h6>
                                                        <div class="x-small text-white opacity-50">No. Order: {{ $result->noorder ?? 'NON-RIS' }}</div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <button type="button" onclick="window.open('{{ route('simrs.hasil.print-pdf') }}?rawat={{ urlencode($result->no_rawat) }}&tgl={{ $result->tgl_periksa }}&jam={{ $result->jam }}', '_blank')" class="btn btn-emerald btn-sm rounded-0 fw-bold px-3 py-1 text-white border-0 d-flex align-items-center gap-1 x-small transition-all">
                                                            <i data-feather="printer" style="width: 12px;"></i> CETAK PDF
                                                        </button>
                                                        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                                                    </div>
                                                </div>

                                                <!-- Body Content -->
                                                <div class="modal-body p-3 bg-light-soft">
                                                    <div class="row g-3">
                                                        
                                                        <!-- Kiri: Informasi Pasien & Klinis -->
                                                        <div class="col-lg-4 d-flex flex-column gap-3">
                                                            
                                                            <!-- Card Profil Pasien -->
                                                            <div class="card border rounded-0 shadow-none bg-white">
                                                                <div class="card-body p-3">
                                                                    <h6 class="fw-bold text-muted mb-3 x-small">INFORMASI PASIEN</h6>
                                                                    
                                                                    <div class="d-flex align-items-start mb-3 border-bottom pb-2">
                                                                        <div class="bg-emerald text-white d-flex align-items-center justify-content-center fw-bolder fs-4 rounded-0 me-3 shadow-sm" style="width: 45px; height: 45px;">
                                                                            {{ substr($result->nm_pasien, 0, 1) }}
                                                                        </div>
                                                                        <div class="flex-grow-1 min-w-0">
                                                                            <h6 class="fw-bold text-dark mb-0 text-truncate" style="font-size: 0.85rem;" title="{{ $result->nm_pasien }}">{{ $result->nm_pasien }}</h6>
                                                                            <div class="x-small text-muted fw-bold">RM: <span class="text-dark">{{ $result->no_rkm_medis }}</span></div>
                                                                            <div class="badge bg-light text-dark border mt-1 rounded-0 font-monospace">{{ $result->no_rawat }}</div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="d-flex flex-column gap-1 x-small fw-bold">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="text-muted">PENJAMIN</span>
                                                                            <span class="text-dark text-end">{{ $result->png_jawab }}</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="text-muted">TGL LAHIR</span>
                                                                            <span class="text-dark">{{ \Carbon\Carbon::parse($result->tgl_lahir)->format('d/m/Y') }}</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="text-muted">USIA / JK</span>
                                                                            <span class="text-dark">{{ \Carbon\Carbon::parse($result->tgl_lahir)->age }} Thn / {{ $result->jk }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Card Detail Klinis -->
                                                            <div class="card border rounded-0 shadow-none bg-white">
                                                                <div class="card-body p-3">
                                                                    <h6 class="fw-bold text-muted mb-3 x-small">KETERANGAN KLINIS</h6>
                                                                    
                                                                    <div class="d-flex flex-column gap-2 x-small">
                                                                        <div>
                                                                            <div class="text-muted fw-bold mb-0">WAKTU PEMERIKSAAN</div>
                                                                            <div class="fw-bold text-dark font-monospace">
                                                                                {{ \Carbon\Carbon::parse($result->tgl_periksa)->format('d M Y') }} &nbsp;|&nbsp; {{ $result->jam }}
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <div class="text-muted fw-bold mb-0">DOKTER PENGIRIM</div>
                                                                            <div class="fw-bold text-dark text-truncate">{{ \Illuminate\Support\Facades\DB::connection('simrs')->table('dokter')->where('kd_dokter', $result->dokter_perujuk)->value('nm_dokter') ?? $result->dokter_perujuk }}</div>
                                                                        </div>
                                                                        <div>
                                                                            <div class="text-muted fw-bold mb-0">SPESIALIS RADIOLOGI</div>
                                                                            <div class="fw-bold text-dark text-truncate">{{ $result->dokter_radiologi }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Rincian Biaya -->
                                                            @if($result->items && $result->items->count() > 0)
                                                                <div class="card border rounded-0 shadow-none bg-white">
                                                                    <div class="card-body p-3">
                                                                        <h6 class="fw-bold text-muted mb-3 x-small">ITEM PEMERIKSAAN (SIMRS)</h6>
                                                                        <ul class="list-unstyled mb-0 d-flex flex-column gap-1 x-small fw-bold">
                                                                            @foreach($result->items as $item)
                                                                                <li class="d-flex justify-content-between align-items-center border-bottom pb-1">
                                                                                    <span class="text-dark text-truncate me-2">{{ $item->nm_perawatan }}</span>
                                                                                    <span class="text-emerald font-monospace">Rp{{ number_format($item->biaya, 0, ',', '.') }}</span>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                        </div>

                                                        <!-- Kanan: Lembar Expertise -->
                                                        <div class="col-lg-8">
                                                            <div class="card border rounded-0 shadow-none bg-white h-100">
                                                                <div class="card-header bg-light border-bottom rounded-0 py-2">
                                                                    <h6 class="fw-bold text-dark mb-0 x-small d-flex align-items-center">
                                                                        <i data-feather="file-text" class="me-2 text-emerald" style="width: 12px;"></i> LEMBAR EXPERTISE
                                                                    </h6>
                                                                </div>
                                                                
                                                                <div class="card-body p-3 overflow-auto" style="min-height: 400px; background-color: #f1f5f9;">
                                                                    <!-- Kertas Laporan -->
                                                                    <div class="bg-white p-4 shadow-sm border mx-auto paper-view" style="min-height: 100%; max-width: 800px; font-family: 'Times New Roman', Times, serif; font-size: 0.95rem; line-height: 1.6; color: #1e293b;">
                                                                        @if($result->hasil)
                                                                            <div style="font-weight: bold; margin-bottom: 15px;">Yth. Teman Sejawat,</div>
                                                                            <div style="white-space: pre-wrap; text-align: justify;">{!! e($result->hasil) !!}</div>
                                                                            
                                                                            <div class="mt-4 text-end d-flex justify-content-end">
                                                                                <div class="text-center" style="width: 200px;">
                                                                                    <div class="mb-3 text-muted" style="font-family: Arial, sans-serif; font-size: 11px;">Divalidasi Oleh,</div>
                                                                                    <div class="fw-bold fs-6 text-dark" style="font-family: Arial, sans-serif; border-bottom: 1px solid #1e293b; padding-bottom: 2px; display:inline-block;">{{ $result->dokter_radiologi }}</div>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted" style="min-height: 300px;">
                                                                                <i data-feather="clock" class="text-muted mb-2" style="width: 32px; height:32px; opacity: 0.5;"></i>
                                                                                <h6 class="fw-bold text-slate-600 mb-0">Menunggu Verifikasi</h6>
                                                                                <span class="x-small">Hasil expertise belum diinput ke SIMRS.</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="check-square" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                                        <h6 class="fw-bold">BELUM ADA DATA HASIL</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                    <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                        SHOWING {{ $results->firstItem() ?? 0 }}-{{ $results->lastItem() ?? 0 }} OF {{ $results->total() }} TOTAL
                    </div>
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            {{-- Previous --}}
                            @if($results->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link rounded-0 fw-bold" style="font-size:0.6rem;letter-spacing:0.5px">‹ PREV</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link rounded-0 fw-bold" href="{{ $results->appends(request()->query())->previousPageUrl() }}" style="font-size:0.6rem;letter-spacing:0.5px">‹ PREV</a>
                                </li>
                            @endif

                            {{-- Page numbers ±2 --}}
                            @foreach($results->getUrlRange(max(1, $results->currentPage() - 2), min($results->lastPage(), $results->currentPage() + 2)) as $page => $url)
                                @if($page == $results->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link rounded-0 fw-bold bg-dark border-dark" style="font-size:0.6rem">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link rounded-0 fw-bold" href="{{ $results->appends(request()->query())->url($page) }}" style="font-size:0.6rem">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if($results->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link rounded-0 fw-bold" href="{{ $results->appends(request()->query())->nextPageUrl() }}" style="font-size:0.6rem;letter-spacing:0.5px">NEXT ›</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link rounded-0 fw-bold" style="font-size:0.6rem;letter-spacing:0.5px">NEXT ›</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Expertise Modal (Flat Clean) -->
    <div class="modal fade" id="expertiseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 bg-dark text-white py-2 rounded-0">
                    <div>
                        <h6 class="modal-title fw-bold mb-0">EDIT EXPERTISE</h6>
                        <div class="x-small text-white opacity-50" id="modal_pasien_name">—</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="x-small text-muted" id="modal_no_rawat">-</div>
                        <div id="image_gallery_container" class="d-flex gap-1"></div>
                    </div>

                    <!-- Template picker -->
                    <div class="mb-2 position-relative">
                        <div class="position-relative">
                            <i data-feather="file-text" class="position-absolute top-50 translate-middle-y ms-2 text-muted" style="width: 12px; pointer-events:none;"></i>
                            <input type="text" id="templateSearchHasil" class="form-control form-control-sm ps-4 rounded-0 medizen-input-minimal"
                                placeholder="Cari template expertise (ketik nama / nomor)..."
                                autocomplete="off">
                        </div>
                        <div id="templateResultsHasil" class="border rounded-0 bg-white shadow-sm mt-1"
                            style="display:none; max-height: 160px; overflow-y: auto; position: absolute; width: 100%; z-index: 9999;">
                        </div>
                    </div>

                    <form id="formExpertise" action="{{ route('simrs.save-expertise') }}" method="POST">
                        @csrf
                        <input type="hidden" name="noorder" id="modal_noorder">
                        <input type="hidden" name="tgl_periksa" id="modal_tgl_periksa">
                        <input type="hidden" name="jam" id="modal_jam">
                        <textarea name="expertise" id="expertise_text"
                            class="form-control border-0 bg-light rounded-0 x-small" rows="12"
                            placeholder="Hasil expertise..." required style="resize: none;"></textarea>
                    </form>
                </div>
                <div class="modal-footer border-0 p-2 pt-0 rounded-0 bg-light">
                    <div class="me-auto x-small text-muted" id="templateInfoHasil" style="display:none;">
                        <i data-feather="check-circle" style="width: 12px;" class="text-emerald"></i>
                        Template dipilih — bisa diedit sebelum menyimpan.
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 x-small fw-bold"
                        data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" form="formExpertise"
                        class="btn btn-sm btn-emerald rounded-0 x-small fw-bold text-white">SIMPAN PERUBAHAN</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Image Preview Modal (Flat) -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 p-2" style="background:#000">
                    <h6 class="modal-title text-white small m-0">IMAGE PREVIEW</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" onclick="bootstrap.Modal.getInstance(document.getElementById('imagePreviewModal')).hide()">
                    <img id="modal_preview_img" src="" style="max-width:100%; max-height:100%; object-fit:contain">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // --- GLOBAL PERSISTENT FILTER ---
    $(document).ready(function() {
        const globalKey = 'medizen_global_filter';
        const urlParams = new URLSearchParams(window.location.search);

        if (!urlParams.has('start_date') && !urlParams.has('search')) {
            const saved = localStorage.getItem(globalKey);
            if (saved) {
                const f = JSON.parse(saved);
                // Map global to local
                if (f.tgl1 || f.start_date) $('input[name="start_date"]').val(f.tgl1 || f.start_date);
                if (f.tgl2 || f.end_date) $('input[name="end_date"]').val(f.tgl2 || f.end_date);
                if (f.keyword || f.search) $('input[name="search"]').val(f.keyword || f.search);
            }
        }

        $('form').on('submit', function() {
            const filters = {
                tgl1: $('input[name="start_date"]').val(),
                tgl2: $('input[name="end_date"]').val(),
                keyword: $('input[name="search"]').val(),
                // Save original names for safety
                start_date: $('input[name="start_date"]').val(),
                end_date: $('input[name="end_date"]').val(),
                search: $('input[name="search"]').val()
            };
            localStorage.setItem(globalKey, JSON.stringify(filters));
        });
    });
    // --- END GLOBAL FILTER ---

    function openExpertiseModal(noorder, name, norawat, expertise, tglValue = '', jamValue = '') {
            document.getElementById('modal_noorder').value = noorder;
            document.getElementById('modal_pasien_name').innerText = name.toUpperCase();
            document.getElementById('modal_no_rawat').innerText = 'No. Rawat: ' + norawat;
            document.getElementById('expertise_text').value = expertise || '';
            document.getElementById('modal_tgl_periksa').value = tglValue;
            document.getElementById('modal_jam').value = jamValue;

            // Reset template picker
            document.getElementById('templateSearchHasil').value = '';
            document.getElementById('templateResultsHasil').style.display = 'none';
            document.getElementById('templateInfoHasil').style.display = 'none';

            const gallery = document.getElementById('image_gallery_container');
            gallery.innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';

            new bootstrap.Modal(document.getElementById('expertiseModal')).show();

            fetch(`/simrs/pacs-images/${noorder}`)
                .then(response => response.json())
                .then(data => {
                    gallery.innerHTML = '';
                    if (data.success && data.images.length > 0) {
                        data.images.slice(0, 3).forEach(imgUrl => {
                            const img = document.createElement('img');
                            img.src = imgUrl;
                            img.className = 'bg-dark cursor-zoom-in';
                            img.style.width = '35px'; img.style.height = '35px'; img.style.objectFit = 'cover';
                            img.onclick = () => {
                                document.getElementById('modal_preview_img').src = imgUrl;
                                new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
                            };
                            gallery.appendChild(img);
                        });
                    } else {
                        gallery.innerHTML = '<span class="x-small text-muted mt-2">NO IMAGE</span>';
                    }
                });
        }

        // ======= TEMPLATE PICKER (Hasil) =======
        let tplTimeout = null;

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('templateSearchHasil');
            const resultsBox  = document.getElementById('templateResultsHasil');
            const infoBar     = document.getElementById('templateInfoHasil');
            const textarea    = document.getElementById('expertise_text');

            searchInput.addEventListener('input', function () {
                clearTimeout(tplTimeout);
                const q = this.value.trim();
                if (q.length < 2) { resultsBox.style.display = 'none'; return; }
                tplTimeout = setTimeout(() => {
                    fetch(`{{ route('simrs.api.templates') }}?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            resultsBox.innerHTML = '';
                            if (!data.length) {
                                resultsBox.innerHTML = '<div class="px-3 py-2 x-small text-muted">Tidak ada template ditemukan.</div>';
                            } else {
                                data.forEach(tpl => {
                                    const item = document.createElement('div');
                                    item.className = 'px-3 py-2 border-bottom x-small fw-bold';
                                    item.style.cssText = 'cursor:pointer;';
                                    item.innerHTML = `<span class="text-emerald me-2">[${tpl.template_number}]</span>${tpl.examination_name}`;
                                    item.addEventListener('mouseenter', () => item.style.background = '#f0fdf4');
                                    item.addEventListener('mouseleave', () => item.style.background = '');
                                    item.addEventListener('click', () => {
                                        textarea.value = tpl.expertise;
                                        searchInput.value = tpl.template_number + ' — ' + tpl.examination_name;
                                        resultsBox.style.display = 'none';
                                        infoBar.style.display = 'flex';
                                        feather.replace();
                                    });
                                    resultsBox.appendChild(item);
                                });
                            }
                            resultsBox.style.display = 'block';
                        });
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                    resultsBox.style.display = 'none';
                }
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .cursor-zoom-in { cursor: zoom-in; }
        .btn-white { background-color: #fff; color: #444; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.5); }
    </style>
@endpush