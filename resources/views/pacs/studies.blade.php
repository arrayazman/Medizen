@extends('layouts.app')
@section('title', 'PACS - Studies')
@section('page-title', 'Studies DICOM (PACS)')

@section('content')
    <div class="card card-medizen">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Imaging Study Registry</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">DICOM ARCHIVE & RETRIEVAL SYSTEM</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dark btn-sm px-3 fw-bold" style="font-size: 0.7rem;"
                    onclick="openModalityModal()">
                    <i data-feather="send" class="me-1" style="width: 14px;"></i> KIRIM KE MODALITY
                </button>
                <a href="{{ route('pacs.search') }}" class="btn btn-emerald btn-sm px-3 fw-bold" style="font-size: 0.7rem;">
                    <i data-feather="search" class="me-1" style="width: 14px;"></i> ADVANCED SEARCH
                </a>
                <a href="{{ route('pacs.index') }}" class="btn btn-emerald-soft btn-sm px-3 fw-bold"
                    style="font-size: 0.7rem;">
                    <i data-feather="grid" class="me-1" style="width: 14px;"></i> PACS DASHBOARD
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filter Bar -->
            <div class="p-3 bg-light-soft border-bottom">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control filter-box-medizen"
                            placeholder="Patient Name..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="accession" class="form-control filter-box-medizen"
                            placeholder="Accession No..." value="{{ request('accession') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date" class="form-control filter-box-medizen"
                            value="{{ request('date') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="modality" class="form-select filter-box-medizen">
                            <option value="">Modalitas: SEMUA</option>
                            @foreach(['CT', 'MR', 'CR', 'DR', 'DX', 'US', 'XA', 'MG', 'NM', 'PT', 'RF', 'SC', 'OT'] as $m)
                                <option value="{{ $m }}" {{ request('modality') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2 col-md-auto">
                        <button type="submit" class="btn btn-dark btn-sm px-3 fw-bold py-2"
                            style="font-size: 0.7rem;">APPLY</button>
                        @if(request()->hasAny(['search', 'date', 'modality', 'accession']))
                            <a href="{{ route('pacs.studies') }}" class="btn btn-light btn-sm border fw-bold ms-1 py-2"
                                style="font-size: 0.7rem;">RESET</a>
                        @endif
                    </div>
                    <div class="ms-auto col-auto">
                        <span class="badge bg-emerald text-white px-2 py-2" style="font-size: 0.65rem; border-radius: 4px;">
                            TOTAL: {{ number_format($totalStudies) }} STUDIES
                        </span>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" id="check-all" class="form-check-input rounded-0 border-dark">
                            </th>
                            <th style="width: 40px;">#</th>
                            <th>Identifier / PID</th>
                            <th>Full Patient Name</th>
                            <th class="text-center">Study Date</th>
                            <th>Procedure Description</th>
                            <th>Accession</th>
                            <th class="text-center">Modality</th>
                            <th class="text-center">Series</th>
                            <th class="text-end">Service Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studies as $i => $s)
                            @php
                                $tags = $s['MainDicomTags'] ?? [];
                                $pTags = $s['PatientMainDicomTags'] ?? [];
                                $patientName = str_replace('^', ', ', $pTags['PatientName'] ?? '-');
                                $studyDate = '-';
                                if (isset($tags['StudyDate']) && $tags['StudyDate'] && strlen($tags['StudyDate']) == 8) {
                                    try {
                                        $studyDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['StudyDate'])->format('d M Y');
                                    } catch (\Exception $e) {
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="study-checkbox form-check-input rounded-0 border-dark" value="{{ $s['ID'] }}">
                                </td>
                                <td class="text-muted fw-bold">
                                    {{ str_pad((($page - 1) * $perPage) + $i + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <code class="text-emerald fw-bold"
                                        style="font-size: 0.7rem;">{{ $pTags['PatientID'] ?? '-' }}</code>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $patientName }}</div>
                                </td>
                                <td class="text-center text-slate-600 fw-bold">{{ $studyDate }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ $tags['StudyDescription'] ?? '-' }}</td>
                                <td><code class="text-slate-500">{{ $tags['AccessionNumber'] ?? '-' }}</code></td>
                                <td class="text-center">
                                    <span class="badge bg-emerald-soft text-emerald px-2 py-1 border"
                                        style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">
                                        {{ $tags['ModalitiesInStudy'] ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark-soft text-dark px-2 py-1"
                                        style="font-size: 0.65rem;">{{ count($s['Series'] ?? []) }} SERS</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('pacs.study-detail', $s['ID']) }}"
                                            class="btn btn-emerald-soft btn-sm p-1 border shadow-sm" title="Detailed Metadata">
                                            <i data-feather="info" style="width: 14px; height: 14px;"></i>
                                        </a>
                                        <button type="button" class="btn btn-emerald btn-sm p-1 border shadow-sm"
                                            title="Launch DICOM Viewer"
                                            onclick="openViewer('{{ app(\App\Services\PACSClient::class)->getOHIFViewerUrl($tags['StudyInstanceUID'] ?? '') }}')">
                                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <a href="{{ url("/studies/{$s['ID']}/archive") }}" target="_blank"
                                            class="btn btn-dark btn-sm p-1 border shadow-sm" title="Export Archive (ZIP)">
                                            <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                        </a>
                                        <form action="{{ route('pacs.delete-study', $s['ID']) }}" method="POST"
                                            class="d-inline swal-confirm" data-swal-title="Delete Imaging Study?"
                                            data-swal-text="Hapus SELURUH gambar dari study ini secara permanen dari server!">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger-soft btn-sm p-1 border shadow-sm"
                                                title="Delete Study Data">
                                                <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="search" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">NO STUDIES FOUND</h6>
                                        <p class="small mb-0">Try adjusting your filter parameters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                    PAGE {{ $page }} • SHOWING {{ count($studies) }} ARCHIVES
                </div>
                <div class="d-flex gap-2">
                    @if($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="btn btn-white btn-sm px-3 fw-bold border">← PREVIOUS</a>
                    @endif
                    @if($hasMore)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="btn btn-white btn-sm px-3 fw-bold border">NEXT →</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modality Selection Modal -->
    <div class="modal fade" id="modalityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-0">
                <div class="modal-header bg-dark text-white rounded-0">
                    <h5 class="modal-title fw-bold" style="font-size: 0.9rem;">Pilih Target Modality</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">TARGET MODALITY (DICOM C-STORE SCU)</label>
                        <select id="target-modality" class="form-select rounded-0 border-dark shadow-none">
                            <option value="">-- Pilih Modalitas --</option>
                            @foreach($pacsModalities as $mod)
                                <option value="{{ $mod }}">{{ $mod }}</option>
                            @endforeach
                        </select>
                        <div class="x-small text-muted mt-2">
                             Data terpilih akan dikirim ke node DICOM yang terdaftar di Orthanc. 
                             Pastikan node target (AET/Host/Port) aktif.
                        </div>
                    </div>

                    <div id="selection-summary" class="alert alert-emerald-soft text-emerald x-small fw-bold mb-0 rounded-0 border-emerald">
                        <i data-feather="check-circle" class="me-2" style="width: 14px;"></i>
                        <span id="selected-count">0</span> STUDIES TERPILIH
                    </div>
                </div>
                <div class="modal-footer bg-light p-2 rounded-0">
                    <button type="button" class="btn btn-emerald btn-sm px-4 fw-bold rounded-0" onclick="submitSendToModality()">KIRIM SEKARANG</button>
                    <button type="button" class="btn btn-light btn-sm px-3 fw-bold border rounded-0" data-bs-dismiss="modal">BATAL</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.study-checkbox');
            
            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = checkAll.checked);
                });
            }
        });

        function openModalityModal() {
            const selected = document.querySelectorAll('.study-checkbox:checked');
            if (selected.length === 0) {
                Swal.fire('INFO', 'Pilih minimal satu study dengan mencentang kotak di sebelah kiri.', 'info');
                return;
            }
            
            document.getElementById('selected-count').textContent = selected.length;
            new bootstrap.Modal(document.getElementById('modalityModal')).show();
        }

        async function submitSendToModality() {
            const modality = document.getElementById('target-modality').value;
            const selected = document.querySelectorAll('.study-checkbox:checked');
            const studyIds = Array.from(selected).map(cb => cb.value);

            if (!modality) {
                Swal.fire('PERINGATAN', 'Silakan pilih target modality terlebih dahulu.', 'warning');
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById('modalityModal')).hide();

            let successCount = 0;
            let failCount = 0;

            Swal.fire({
                title: 'Sedang Mengirim...',
                html: `Memproses pengiriman <b id="store-current">0</b> / ${studyIds.length} data ke <b>${modality}</b>.`,
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            for (let i = 0; i < studyIds.length; i++) {
                const id = studyIds[i];
                console.log('Sending Study ID:', id, 'to', modality);
                try {
                    const response = await fetch('{{ route("pacs.send-to-modality") }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify({ 
                            modality: modality,
                            study_id: id,
                            id: id
                        })
                    });
                    
                    const res = await response.json();
                    if (res.success) successCount++;
                    else failCount++;
                } catch (err) {
                    failCount++;
                    console.error('Store error:', err);
                }
                
                if (document.getElementById('store-current')) {
                    document.getElementById('store-current').textContent = i + 1;
                }
            }

            Swal.fire({
                icon: successCount > 0 ? 'success' : 'error',
                title: 'Selesai',
                text: `Berhasil terkirim: ${successCount}, Gagal: ${failCount}`
            }).then(() => {
                if (successCount > 0) window.location.reload();
            });
        }
    </script>
@endpush