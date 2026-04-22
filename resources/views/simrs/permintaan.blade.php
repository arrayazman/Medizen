@extends('layouts.app')

@section('page-title', 'Permintaan SIMRS')

@section('content')
    <div class="card card-medizen rounded-0 border-0 shadow-none">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Permintaan Radiologi (SIMRS)</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">REALTIME QUEUE FROM SIMRS KHANZA</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                    onclick="openModalityTargetModal()">
                    <i data-feather="send" class="me-2" style="width: 14px;"></i> KIRIM KE MODALITY
                </button>
                <button class="btn btn-emerald-soft text-emerald btn-sm px-3 shadow-none fw-bold rounded-0 border-emerald" style="font-size: 0.7rem; border-color: rgba(16, 185, 129, 0.4) !important;"
                    onclick="batchUpdatePACS()">
                    <i data-feather="refresh-cw" class="me-2" style="width: 14px;"></i> UPDATE TERPILIH PACS
                </button>
                <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                    type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i data-feather="filter" class="me-2" style="width: 14px;"></i> ADVANCED FILTER
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Integrated Filter Bar -->
            <div class="collapse {{ request('start_date') ? 'show' : '' }} p-1 bg-light-soft border-bottom"
                id="filterCollapse">
                <form action="{{ route('simrs.permintaan') }}" method="GET" class="p-2">
                    <div class="row g-1">
                        <div class="col-md-2 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AWAL</label>
                            <input type="date" name="start_date" class="form-control form-control-sm rounded-0"
                                value="{{ $startDate }}" style="font-size: 0.6rem; height: 28px;">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AKHIR</label>
                            <input type="date" name="end_date" class="form-control form-control-sm rounded-0"
                                value="{{ $endDate }}" style="font-size: 0.6rem; height: 28px;">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">STATUS</label>
                            <select name="status" class="form-select form-select-sm rounded-0"
                                style="font-size: 0.6rem; height: 28px;">
                                <option value="all" {{ $status == 'all' ? 'selected' : '' }}>SEMUA</option>
                                <option value="ralan" {{ $status == 'ralan' ? 'selected' : '' }}>RALAN</option>
                                <option value="ranap" {{ $status == 'ranap' ? 'selected' : '' }}>RANAP</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">CARI PASIEN / RM /
                                ORDER</label>
                            <div class="position-relative">
                                <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                    style="width: 10px;"></i>
                                <input type="text" name="search" class="form-control form-control-sm ps-4 rounded-0"
                                    placeholder="Search..." value="{{ $search }}" style="font-size: 0.6rem; height: 28px;">
                            </div>
                        </div>
                        <div class="col-md-2 col-12 d-flex align-items-end gap-1">
                            <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 flex-fill"
                                style="font-size: 0.6rem; height: 28px;">
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
                            <th class="py-2 small ps-3" style="width: 40px;">
                                <input type="checkbox" id="check-all" class="form-check-input rounded-0 border-dark">
                            </th>
                            <th class="py-2 small">Patient & Order</th>
                            <th class="py-2 small text-center">Schedule</th>
                            <th class="py-2 small text-center">Origin / Unit</th>
                            <th class="py-2 small text-center">Status</th>
                            <th class="py-2 small text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr onclick="openOrderDetailModal('{{ $order->noorder }}')"
                                style="cursor: pointer;">
                                <td class="ps-3 py-2" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="order-checkbox form-check-input rounded-0 border-dark" value="{{ $order->noorder }}">
                                </td>
                                <td class="py-2">
                                    <div class="fw-bold text-slate-800 small">{{ strtoupper($order->nm_pasien) }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">RM: <span class="privacy-mask">{{ $order->no_rkm_medis }}</span> |
                                        #{{ $order->noorder }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    <div class="fw-bold text-slate-700" style="font-size: 0.7rem;">
                                        {{ \Carbon\Carbon::parse($order->tgl_permintaan)->format('d/m/Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;"><i data-feather="clock" class="me-1"
                                            style="width: 10px;"></i> {{ substr($order->jam_permintaan, 0, 5) }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    <span
                                        class="badge bg-{{ $order->status_rawat == 'ralan' ? 'emerald' : 'orange' }}-soft text-{{ $order->status_rawat == 'ralan' ? 'emerald' : 'orange' }} border px-1 py-0 mb-1"
                                        style="font-size: 0.6rem;">{{ strtoupper($order->status_rawat) }}</span>
                                    <div class="text-muted text-truncate mx-auto small"
                                        style="max-width: 120px; font-size: 0.6rem;">{{ $order->nm_poli }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    @if($order->has_expertise)
                                        <span class="badge-modern bg-emerald text-white" style="transform: scale(0.85);">HASIL
                                            OK</span>
                                    @elseif($order->tgl_sampel != '0000-00-00')
                                        <span class="badge-modern bg-info text-white" style="transform: scale(0.85);">SAMPEL</span>
                                    @else
                                        <span class="badge-modern bg-light text-muted border"
                                            style="transform: scale(0.85);">ANTRE</span>
                                    @endif
                                </td>
                                <td class="py-2 text-end pe-3" onclick="event.stopPropagation();">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <button onclick="openOrderDetailModal('{{ $order->noorder }}')"
                                            class="btn btn-light rounded-0 px-2 py-1 x-small fw-bold">DETAIL</button>
                                        @if($order->tgl_sampel == '0000-00-00')
                                            <button class="btn btn-dark rounded-0 px-2 py-1 x-small fw-bold"
                                                onclick="takeSample(this)" data-noorder="{{ $order->noorder }}">SAMPEL</button>
                                        @endif
                                        <button class="btn btn-emerald rounded-0 px-2 py-1 x-small fw-bold text-white border-0"
                                            onclick='openExpertiseModal("{{ $order->noorder }}", "{{ $order->nm_pasien }}", "{{ $order->no_rawat }}", {!! json_encode($order->local_expertise_simrs ?: $order->local_expertise) !!}, "{{ $order->tgl_hasil }}", "{{ $order->jam_hasil }}")'>HASIL</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="file-text" style="width: 48px; height: 48px;"
                                            class="mb-3 text-muted"></i>
                                        <h6 class="fw-bold">TIDAK ADA DATA PERMINTAAN</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                    <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                        SHOWING {{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }} OF {{ $orders->total() }} TOTAL
                    </div>
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            {{-- Previous --}}
                            @if($orders->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link rounded-0 fw-bold" style="font-size:0.6rem;letter-spacing:0.5px">‹ PREV</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link rounded-0 fw-bold" href="{{ $orders->appends(request()->query())->previousPageUrl() }}" style="font-size:0.6rem;letter-spacing:0.5px">‹ PREV</a>
                                </li>
                            @endif

                            {{-- Page numbers ±2 --}}
                            @foreach($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                                @if($page == $orders->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link rounded-0 fw-bold bg-dark border-dark" style="font-size:0.6rem">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link rounded-0 fw-bold" href="{{ $orders->appends(request()->query())->url($page) }}" style="font-size:0.6rem">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if($orders->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link rounded-0 fw-bold" href="{{ $orders->appends(request()->query())->nextPageUrl() }}" style="font-size:0.6rem;letter-spacing:0.5px">NEXT ›</a>
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

    <!-- Order Detail Modal (Full) -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 bg-dark text-white py-2 rounded-0">
                    <h6 class="modal-title fw-bold">ORDER DETAIL</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 bg-light" id="orderDetailContent">
                    <div class="d-flex justify-content-center py-5">
                        <div class="spinner-border text-emerald" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expertise Modal (Flat Clean) -->
    <div class="modal fade" id="expertiseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 bg-dark text-white py-2 rounded-0">
                    <h6 class="modal-title fw-bold">INPUT EXPERTISE</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <!-- Patient info row -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="fw-bold mb-0" id="modal_pasien_name">-</div>
                            <div class="x-small text-muted" id="modal_no_rawat">-</div>
                        </div>
                        <div id="image_gallery_container" class="d-flex gap-1"></div>
                    </div>

                    <!-- Template picker -->
                    <div class="mb-2 d-flex gap-2 align-items-center">
                        <div class="flex-fill position-relative">
                            <i data-feather="file-text" class="position-absolute top-50 translate-middle-y ms-2 text-muted" style="width: 12px;"></i>
                            <input type="text" id="templateSearch" class="form-control form-control-sm ps-4 rounded-0 medizen-input-minimal"
                                placeholder="Cari template expertise (ketik nama / nomor)..."
                                autocomplete="off">
                        </div>
                        <div id="templateDropdownWrapper" class="position-relative" style="display:none;">
                        </div>
                    </div>
                    <!-- Template results -->
                    <div id="templateResults" class="border rounded-0 bg-white shadow-sm mb-2"
                        style="display:none; max-height: 160px; overflow-y: auto; z-index: 9999; position: relative;">
                    </div>

                    <form id="formExpertise" action="{{ route('simrs.save-expertise') }}" method="POST">
                        @csrf
                        <input type="hidden" name="noorder" id="modal_noorder">
                        <input type="hidden" name="tgl_periksa" id="modal_tgl_periksa">
                        <input type="hidden" name="jam" id="modal_jam">
                        <textarea name="expertise" id="expertise_text"
                            class="form-control border-0 bg-light rounded-0 x-small" rows="12"
                            placeholder="Ketik hasil pemeriksaan disini, atau pilih dari template di atas..." required
                            style="resize: none;"></textarea>
                    </form>
                </div>
                <div class="modal-footer border-0 p-2 pt-0 rounded-0 bg-light">
                    <div class="me-auto x-small text-muted" id="templateInfo" style="display:none;">
                        <i data-feather="check-circle" style="width: 12px;" class="text-emerald"></i>
                        Template dipilih — bisa diedit sebelum menyimpan.
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 x-small fw-bold"
                        data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" form="formExpertise"
                        class="btn btn-sm btn-emerald rounded-0 x-small fw-bold text-white">SIMPAN HASIL</button>
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
                <div class="modal-body d-flex align-items-center justify-content-center p-0"
                    onclick="bootstrap.Modal.getInstance(document.getElementById('imagePreviewModal')).hide()">
                    <img id="modal_preview_img" src="" style="max-width:100%; max-height:100%; object-fit:contain">
                </div>
            </div>
        </div>
    </div>

    <!-- Modality Selection Modal -->
    <div class="modal fade" id="targetModalityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-0">
                <div class="modal-header bg-dark text-white rounded-0 py-2">
                    <h5 class="modal-title fw-bold" style="font-size: 0.85rem;">PILIH TARGET MODALITY</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2" style="font-size: 0.65rem;">DESTINATION NODE (PACS)</label>
                        <select id="target-modality-select" class="form-select form-select-sm rounded-0 border-dark shadow-none" style="font-size: 0.75rem;">
                            <option value="">-- Pilih Modalitas --</option>
                            @foreach($pacsModalities as $mod)
                                <option value="{{ $mod }}">{{ strtoupper($mod) }}</option>
                            @endforeach
                        </select>
                        <div class="x-small text-muted mt-2" style="font-size: 0.6rem;">
                             Permintaan terpilih akan dikirimkan ke server/alat tujuan. Pastikan target terhubung ke jaringan.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 rounded-0">
                    <button type="button" class="btn btn-emerald btn-sm px-4 fw-bold rounded-0" style="font-size: 0.7rem;" onclick="executeBatchSendToModality()">KIRIM SEKARANG</button>
                    <button type="button" class="btn btn-light btn-sm px-3 fw-bold border rounded-0" style="font-size: 0.7rem;" data-bs-dismiss="modal">BATAL</button>
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
                if (f.status) $('select[name="status"]').val(f.status);
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
                search: $('input[name="search"]').val(),
                status: $('select[name="status"]').val()
            };
            localStorage.setItem(globalKey, JSON.stringify(filters));
        });
    });
    // --- END GLOBAL FILTER ---

    function openOrderDetailModal(noorder) {
            const modalElement = document.getElementById('orderDetailModal');
            const contentBox = document.getElementById('orderDetailContent');
            const modal = new bootstrap.Modal(modalElement);
            
            contentBox.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-emerald" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            fetch(`/simrs/detail/${noorder}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => {
                contentBox.innerHTML = html;
                feather.replace();
            })
            .catch(err => {
                contentBox.innerHTML = `<div class="alert alert-danger rounded-0">Gagal memuat detail: ${err.message}</div>`;
            });
        }

        function openImageDetail(url) {
            document.getElementById('modal_preview_img').src = url;
            new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
        }

        function takeSampleDetail(noorder, target = '') {
            Swal.fire({
                title: 'CATAT SAMPEL?',
                text: "Sinkron ke RIS & Kirim Worklist ke Modality",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                confirmButtonText: 'YA, PROSES',
                cancelButtonText: 'BATAL',
                customClass: { popup: 'rounded-0', confirmButton: 'rounded-0', cancelButton: 'rounded-0' }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    fetch('{{ route("simrs.take-sample") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ noorder: noorder, target: target })
                    }).then(r => r.json()).then(res => {
                        if (res.success) {
                            openOrderDetailModal(noorder);
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire('GAGAL', res.message, 'error');
                        }
                    });
                }
            });
        }

        function updateLocalStatus(orderId, status) {
            fetch('{{ route("simrs.update-status") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ order_id: orderId, status: status })
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    // Refresh modal content to show new status/buttons
                    const noorder = document.querySelector('input[name="noorder"]')?.value;
                    if(noorder) openOrderDetailModal(noorder);
                } else {
                    Swal.fire('GAGAL', res.message, 'error');
                }
            });
        }

        function sendWorklistDetail(orderId, target) {
            Swal.fire({ title: 'Mengirim Worklist...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch('{{ route("simrs.send-worklist-direct") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ order_id: orderId, target: target })
            }).then(r => r.json()).then(res => {
                Swal.fire({ icon: res.success ? 'success' : 'error', title: res.success ? 'Berhasil' : 'Gagal', text: res.message, timer: 2000 });
            });
        }

        // Global Event Delegation for Dynamic Elements in Modal
        $(document).on('submit', '#formUploadDicom', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const noorder = formData.get('noorder');
            const progress = $('#uploadProgress');
            const progressBar = progress.find('.progress-bar');
            
            progress.removeClass('d-none');
            progressBar.css('width', '0%');

            $.ajax({
                url: '{{ route("simrs.upload-dicom") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            progressBar.css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Upload Berhasil', text: res.message, timer: 1500 });
                        openOrderDetailModal(noorder);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        progress.addClass('d-none');
                    }
                },
                error: function(err) {
                    Swal.fire('Error', 'Gagal upload file.', 'error');
                    progress.addClass('d-none');
                }
            });
        });

        function updatePACSAccession(noorder) {
            Swal.fire({
                title: 'Sinkronisasi Accession Number?',
                text: "Ini akan memperbarui tag Accession Number di PACS (Orthanc) agar sesuai dengan nomor order SIMRS.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'SINKRONKAN',
                cancelButtonText: 'BATAL',
                customClass: { popup: 'rounded-0', confirmButton: 'rounded-0', cancelButton: 'rounded-0' }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('{{ route("simrs.update-pacs-acc") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ noorder: noorder })
                    }).then(r => r.json()).then(res => {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                if (document.getElementById('orderDetailModal').classList.contains('show')) {
                                    openOrderDetailModal(noorder);
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire('GAGAL', res.message, 'error');
                        }
                    }).catch(err => {
                        Swal.fire('ERROR', 'Terjadi kesalahan sistem: ' + err.message, 'error');
                    });
                }
            });
        }

        async function batchUpdatePACS() {
            const selectedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
            const orders = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (!orders || orders.length === 0) {
                Swal.fire('INFO', 'Pilih minimal satu permintaan dengan mencentang kotak di sebelah kiri.', 'info');
                return;
            }

            const result = await Swal.fire({
                title: 'Update Terpilih ke PACS?',
                text: `Terdapat ${orders.length} permintaan terpilih yang akan disinkronkan ke PACS.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'MULAI UPDATE',
                cancelButtonText: 'BATAL',
                customClass: { popup: 'rounded-0', confirmButton: 'rounded-0', cancelButton: 'rounded-0' }
            });

            if (result.isConfirmed) {
                let successCount = 0;
                let failCount = 0;

                Swal.fire({
                    title: 'Memproses Update Terpilih...',
                    html: `Progress: <b id="batch-current">0</b> / ${orders.length}`,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); },
                    customClass: { popup: 'rounded-0' }
                });

                for (let i = 0; i < orders.length; i++) {
                    const noorder = orders[i];
                    try {
                        const response = await fetch('{{ route("simrs.update-pacs-acc") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ noorder: noorder })
                        });
                        const res = await response.json();
                        if (res.success) successCount++;
                        else failCount++;
                    } catch (e) {
                        failCount++;
                    }
                    if (document.getElementById('batch-current')) {
                        document.getElementById('batch-current').textContent = i + 1;
                    }
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Selesai',
                    text: `Berhasil: ${successCount}, Gagal: ${failCount}`,
                    customClass: { popup: 'rounded-0', confirmButton: 'rounded-0' }
                }).then(() => window.location.reload());
            }
        }

        function openExpertiseModal(noorder, name, norawat, expertise, tglValue = '', jamValue = '') {
            document.getElementById('modal_noorder').value = noorder;
            document.getElementById('modal_pasien_name').innerText = name.toUpperCase();
            document.getElementById('modal_no_rawat').innerText = norawat;
            document.getElementById('expertise_text').value = expertise || '';
            document.getElementById('modal_tgl_periksa').value = tglValue;
            document.getElementById('modal_jam').value = jamValue;

            document.getElementById('templateSearch').value = '';
            document.getElementById('templateResults').style.display = 'none';
            document.getElementById('templateInfo').style.display = 'none';

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

        function takeSample(btn) {
            const noorder = typeof btn === 'string' ? btn : btn.getAttribute('data-noorder');
            Swal.fire({
                title: 'CATAT SAMPEL?',
                text: "Sinkron ke PACS & SIMRS",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                confirmButtonText: 'YA',
                cancelButtonText: 'TIDAK',
                customClass: { popup: 'rounded-0', confirmButton: 'rounded-0', cancelButton: 'rounded-0' }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("simrs.take-sample") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ noorder: noorder })
                    }).then(r => r.json()).then(res => {
                        if (res.success) window.location.reload();
                        else Swal.fire('GAGAL', res.message, 'error');
                    });
                }
            });
        }

        let templateSearchTimeout = null;

        document.addEventListener('DOMContentLoaded', function () {
            // Check All Handler
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = checkAll.checked);
                });
            }

            // Template Picker Handler
            const searchInput  = document.getElementById('templateSearch');
            const resultsBox   = document.getElementById('templateResults');
            const infoBar      = document.getElementById('templateInfo');
            const textarea     = document.getElementById('expertise_text');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(templateSearchTimeout);
                    const q = this.value.trim();
                    if (q.length < 2) {
                        resultsBox.style.display = 'none';
                        return;
                    }
                    templateSearchTimeout = setTimeout(() => {
                        fetch(`{{ route('simrs.api.templates') }}?q=${encodeURIComponent(q)}`)
                            .then(r => r.json())
                            .then(data => {
                                resultsBox.innerHTML = '';
                                if (!data.length) {
                                    resultsBox.innerHTML = '<div class="px-3 py-2 x-small text-muted">Tidak ada template ditemukan.</div>';
                                } else {
                                    data.forEach(tpl => {
                                        const item = document.createElement('div');
                                        item.className = 'px-3 py-2 border-bottom x-small fw-bold cursor-pointer text-start';
                                        item.style.cssText = 'cursor:pointer; transition: background 0.1s;';
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
            }
        });

        function openModalityTargetModal() {
            const selected = document.querySelectorAll('.order-checkbox:checked');
            if (selected.length === 0) {
                Swal.fire('INFO', 'Silakan pilih permintaan dengan mencentang kotak di sisi kiri.', 'info');
                return;
            }
            new bootstrap.Modal(document.getElementById('targetModalityModal')).show();
        }

        async function executeBatchSendToModality() {
            const target = document.getElementById('target-modality-select').value;
            const selected = document.querySelectorAll('.order-checkbox:checked');
            const noorders = Array.from(selected).map(cb => cb.value);

            if (!target) {
                Swal.fire('PERINGATAN', 'Silakan pilih target modality terlebih dahulu.', 'warning');
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById('targetModalityModal')).hide();

            let successCount = 0;
            let failCount = 0;

            Swal.fire({
                title: 'Sedang Mengirim...',
                html: `Memproses data <b id="send-current">0</b> / ${noorders.length} ke <b>${target.toUpperCase()}</b>.`,
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            for (let i = 0; i < noorders.length; i++) {
                const noorder = noorders[i];
                try {
                    const response = await fetch('{{ route("simrs.send-to-modality") }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify({ noorder: noorder, target: target })
                    });
                    const res = await response.json();
                    if (res.success) successCount++;
                    else failCount++;
                } catch (err) {
                    failCount++;
                }

                if (document.getElementById('send-current')) {
                    document.getElementById('send-current').textContent = i + 1;
                }
            }

            Swal.fire({
                icon: successCount > 0 ? 'success' : 'error',
                title: 'Selesai',
                text: `Kirim ke Modalitas SELESAI. Berhasil: ${successCount}, Gagal: ${failCount}.`
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        .cursor-zoom-in { cursor: zoom-in; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.5); }
    </style>
@endpush