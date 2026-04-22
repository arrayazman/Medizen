@extends('layouts.app')

@section('page-title', 'Bridging Imaging Study (SIMRS)')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Bridging Imaging Study (SIMRS)</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">SATUSEHAT INTEGRATION FROM SIMRS DATA</div>
        </div>
                <div class="d-flex gap-2">
                        <div class="dropdown">
                <button class="btn btn-dark btn-sm px-3 shadow-none fw-bold rounded-0 dropdown-toggle" style="font-size: 0.7rem;" type="button" data-bs-toggle="dropdown">
                    <i data-feather="check-square" class="me-1" style="width: 14px;"></i> PILIH DATA
                </button>
                <ul class="dropdown-menu rounded-0 shadow border-0" style="font-size: 0.75rem;">
                    <li><a class="dropdown-item" href="#" onclick="$('.row-check:not(:disabled)').prop('checked', true); return false;">Select All (Semua)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="$('.row-check').prop('checked', false); $('.row-check-pending:not(:disabled)').prop('checked', true); return false;">Select Pending (Belum Terkirim)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="$('.row-check').prop('checked', false); $('.row-check-sent:not(:disabled)').prop('checked', true); return false;">Select Sent (Update Kirim)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="$('.row-check').prop('checked', false); return false;">Batalkan Pilihan</a></li>
                </ul>
            </div>
            <button id="btnKirimBaru" class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;">
                <i data-feather="send" class="me-2" style="width: 14px;"></i> KIRIM DATA BARU
            </button>
            <button id="btnUpdateData" class="btn btn-info btn-sm px-3 shadow-none fw-bold rounded-0 text-white" style="font-size: 0.7rem;">
                <i data-feather="refresh-cw" class="me-2" style="width: 14px;"></i> UPDATE TERPILIH
            </button>
            <div class="d-flex align-items-center bg-light px-2 border border-start-0 border-end-0 border-top-0" style="border-bottom-width: 2px !important;">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="cbForce" style="cursor: pointer; width: 2em;">
                    <label class="form-check-label fw-bold text-slate-700" for="cbForce" style="font-size: 0.65rem; cursor: pointer; white-space: nowrap;">FORCE SEND</label>
                </div>
            </div>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Integrated Filter Bar -->
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.kirim-imaging') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-2 col-6">
                        <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AWAL</label>
                        <input type="date" name="tgl1" class="form-control form-control-sm rounded-0"
                            value="{{ $tgl1 }}" style="font-size: 0.6rem; height: 28px;">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TGL AKHIR</label>
                        <input type="date" name="tgl2" class="form-control form-control-sm rounded-0"
                            value="{{ $tgl2 }}" style="font-size: 0.6rem; height: 28px;">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">TAMPILKAN DATA</label>
                        <select name="per_page" class="form-select form-select-sm rounded-0" style="font-size: 0.6rem; height: 28px;">
                            <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25 Data</option>
                            <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50 Data</option>
                            <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100 Data</option>
                            <option value="all" {{ ($perPage ?? 25) == 'all' ? 'selected' : '' }}>Semua Data</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12">
                        <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">CARI PASIEN / RM / ORDER</label>
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                style="width: 10px;"></i>
                            <input type="text" name="keyword" class="form-control form-control-sm ps-4 rounded-0"
                                placeholder="Search..." value="{{ $keyword }}" style="font-size: 0.6rem; height: 28px;">
                        </div>
                    </div>
                    <div class="col-md-2 col-12 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 flex-fill"
                            style="font-size: 0.6rem; height: 28px;">
                            <i data-feather="refresh-cw" class="me-1" style="width: 10px;"></i> TAMPILKAN
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen mb-0" id="table-data">
                <thead>
                    <tr>
                        <th class="py-2 small ps-3">Patient & Info</th>
                        <th class="py-2 small">Procedure / Order</th>
                        <th class="py-2 small text-center">ServiceRequest</th>
                        <th class="py-2 small text-center">ImagingStudy</th>
                        <th class="py-2 small text-center">Status</th>
                        <th class="py-2 small text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="data-list">
                    @forelse ($orders as $row)
                        <tr>
                            <td class="ps-3 align-middle" id="td-check-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}">
                                <div class="form-check mb-0">
                                <input class="form-check-input row-check {{ $row->id_imaging ? 'row-check-sent' : 'row-check-pending' }}" type="checkbox" value="{{ json_encode($row) }}">
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800 small">{{ strtoupper($row->nm_pasien) }}</div>
                                <div class="text-muted" style="font-size: 0.6rem;">RM: <span class="privacy-mask">{{ $row->no_rkm_medis }}</span> | NIK: <span class="privacy-mask">{{ $row->no_ktp_pasien ?: '-' }}</span></div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-700 small"><span class="privacy-mask">{{ $row->nm_perawatan }}</span></div>
                                <div class="text-muted" style="font-size: 0.6rem;">ACSN: {{ $row->noorder }} | {{ $row->tgl_permintaan }} {{ $row->jam_permintaan }}</div>
                            </td>
                            <td class="text-center align-middle">
                                <code id="label-sr-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}" class="x-small text-slate-500">{{ $row->id_servicerequest ?: '-' }}</code>
                            </td>
                            <td class="text-center align-middle">
                                <code id="label-imaging-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}" class="x-small text-emerald fw-bold">{{ $row->id_imaging ?: '-' }}</code>
                            </td>
                            <td class="text-center" id="td-status-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}">
                                @if($row->id_imaging)
                                    <span class="badge-modern bg-emerald text-white">SENT</span>
                                @else
                                    <span class="badge-modern bg-light text-muted border">PENDING</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                @if($row->id_imaging)
                                    <button id="btn-single-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}" class="btn btn-info rounded-0 px-2 py-1 x-small fw-bold text-white border-0" 
                                            onclick='kirimData({{ json_encode($row) }}, "{{ md5($row->noorder."_".$row->kd_jenis_prw) }}")'>
                                        RE-SYNC / UPDATE
                                    </button>
                                @else
                                    <button id="btn-single-{{ md5($row->noorder.'_'.$row->kd_jenis_prw) }}" class="btn btn-emerald rounded-0 px-2 py-1 x-small fw-bold text-white border-0" 
                                            onclick='kirimData({{ json_encode($row) }}, "{{ md5($row->noorder."_".$row->kd_jenis_prw) }}")'>
                                        KIRIM DATA
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center opacity-50">
                                    <i data-feather="calendar" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                                    <h6 class="fw-bold small text-uppercase">TIDAK ADA DATA HASIL RADIOLOGI</h6>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Footer -->
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

<!-- Modal Log (Medizen Style) -->
<div class="modal fade medizen-modal-minimal" id="modalLog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title px-0">LOG PENGIRIMAN SATUSEHAT</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="log-content" class="bg-dark text-emerald font-monospace p-3" 
                     style="font-size: 0.65rem; height: 350px; overflow-y: auto; line-height: 1.5;">
                </div>
            </div>
            <div class="modal-footer bg-light py-1 px-3">
                <button type="button" class="btn btn-dark btn-sm rounded-0 fw-bold x-small" data-bs-dismiss="modal">CLOSE WINDOW</button>
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

        if (!urlParams.has('tgl1') && !urlParams.has('keyword')) {
            const saved = localStorage.getItem(globalKey);
            if (saved) {
                const f = JSON.parse(saved);
                if (f.tgl1) $('input[name="tgl1"]').val(f.tgl1);
                if (f.tgl2) $('input[name="tgl2"]').val(f.tgl2);
                if (f.keyword) $('input[name="keyword"]').val(f.keyword);
            }
        }

        $('form').on('submit', function() {
            const filters = {
                tgl1: $('input[name="tgl1"]').val(),
                tgl2: $('input[name="tgl2"]').val(),
                keyword: $('input[name="keyword"]').val()
            };
            localStorage.setItem(globalKey, JSON.stringify(filters));
        });
    });
    // --- END GLOBAL FILTER ---

    const appendLog = (type, msg) => {
        let color = 'text-white';
        if (type === 'ok') color = 'text-emerald fw-bold';
        if (type === 'err') color = 'text-danger fw-bold';
        if (type === 'info') color = 'text-info';
        $('#log-content').append(`<div><span class="opacity-50">[${new Date().toLocaleTimeString()}]</span> <span class="${color}">${msg}</span></div>`);
        $('#log-content').scrollTop($('#log-content')[0].scrollHeight);
    };

    function updateRowUI(rowHash, newId) {
        $(`#td-check-${rowHash}`).empty();
        $(`#td-status-${rowHash}`).html('<span class="badge-modern bg-emerald text-white">SENT</span>');
        $(`#label-imaging-${rowHash}`).text(newId);
        $(`#btn-single-${rowHash}`).removeClass('btn-emerald').addClass('btn-info').text('RE-SYNC / UPDATE');
    }

    function updateRowPending(rowHash, acsn) {
        $(`#td-check-${rowHash}`).empty();
        $(`#td-status-${rowHash}`).html('<span class="badge-modern bg-warning text-dark">SENT→SYNC</span>');
        $(`#label-imaging-${rowHash}`).text('ACSN: ' + acsn + ' (menunggu sync...)');
        $(`#btn-single-${rowHash}`).removeClass('btn-emerald').addClass('btn-secondary').text('MENUNGGU SYNC');
    }

    async function kirimData(row, rowHash) {
        const modal = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty();
        modal.show();

        const isForce = $('#cbForce').is(':checked') ? 1 : 0;

        try {
            appendLog('info', 'MENGHUBUNGI ORTHANC & DICOM ROUTER → SATUSEHAT...');
            if (isForce) appendLog('err', '!!! MODE FORCE SEND AKTIF: AKAN MENGIRIM ULANG FILE DICOM !!!');

            const res = await $.post('{{ route("satusehat.kirim-imaging.post") }}', {
                _token: '{{ csrf_token() }}',
                force: isForce,
                ...row
            });
            
            if (res.logs) res.logs.forEach(l => appendLog(l.type, l.msg.toUpperCase()));
            
            if (res.ok) {
                if (res.id_imaging) {
                    appendLog('ok', 'SELESAI — FHIR ID: ' + res.id_imaging);
                    updateRowUI(rowHash, res.id_imaging);
                } else {
                    appendLog('info', 'DIKIRIM KE DICOM ROUTER. ID IMAGING BELUM TERSEDIA — SYNC MANUAL DIPERLUKAN.');
                    updateRowPending(rowHash, res.acsn || row.noorder);
                }
            } else {
                appendLog('err', 'GAGAL: ' + (res.msg || '').toUpperCase());
            }
        } catch (xhr) {
            appendLog('err', 'FATAL ERROR: ' + (xhr.responseText || xhr.message || 'Unknown error'));
        }
    }

    // Master Checkbox
    $('#checkAll').on('change', function() {
        $('.row-check:not(:disabled)').prop('checked', this.checked);
    });

    // Mass Send (Kirim Baru)
    $('#btnKirimBaru').on('click', async function() {
        const selected = $('.row-check-pending:checked');
        if(selected.length === 0) return Swal.fire('Info', 'Silakan pilih data dengan status PENDING untuk dikirim!', 'info');
        processBatch(selected, 'KIRIM DATA BARU');
    });

    // Mass Update
    $('#btnUpdateData').on('click', async function() {
        const selected = $('.row-check-sent:checked');
        if(selected.length === 0) return Swal.fire('Info', 'Silakan pilih data dengan status SENT untuk diperbarui!', 'info');
        processBatch(selected, 'UPDATE DATA TERPILIH');
    });

    async function processBatch(selected, title) {
        const modal = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty();
        modal.show();

        const isForce = $('#cbForce').is(':checked') ? 1 : 0;

        appendLog('info', `MEMULAI BATCH: ${title} (${selected.length} DATA)`);
        if (isForce) appendLog('err', '!!! WARNING: MODE FORCE SEND AKTIF UNTUK SELURUH BATCH !!!');
        appendLog('info', '----------------------------------------');

        for(let i = 0; i < selected.length; i++) {
            const cb = $(selected[i]);
            const row = JSON.parse(cb.val());
            const rowHash = cb.closest('tr').find('td[id^="td-check-"]').attr('id').replace('td-check-', '');
            
            appendLog('info', `[${i+1}/${selected.length}] MEMPROSES IMAGING STUDY: ${row.noorder} ...`);
            
            try {
                const res = await $.post('{{ route("satusehat.kirim-imaging.post") }}', {
                    _token: '{{ csrf_token() }}',
                    force: isForce,
                    ...row
                });
                
                if (res.logs) res.logs.forEach(l => appendLog(l.type, l.msg.toUpperCase()));

                if (res.ok) {
                    if (res.id_imaging) {
                        appendLog('ok', `SELESAI: ${row.noorder} — ID: ${res.id_imaging}.`);
                        updateRowUI(rowHash, res.id_imaging);
                    } else {
                        appendLog('info', `DIKIRIM (${row.noorder}). ID IMAGING BELUM TERSEDIA — SYNC MANUAL DIPERLUKAN.`);
                        updateRowPending(rowHash, res.acsn || row.noorder);
                    }
                } else {
                    appendLog('err', `GAGAL (${row.noorder}): ` + (res.msg || '').toUpperCase());
                }
            } catch (e) {
                appendLog('err', `FATAL ERROR (${row.noorder}): ` + (e.responseText || e.message));
            }
            appendLog('info', '----------------------------------------');
        }
        appendLog('ok', 'SELURUH PROSES BATCH SELESAI.');
    }
</script>
@endpush
