@extends('layouts.app')

@section('page-title', 'Kirim Vaksin SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Kirim Vaksinasi (Immunization)</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">BRIDGING DATA IMUNISASI & VAKSINASI PASIEN KE SATUSEHAT Resource</div>
        </div>
        <div class="d-flex gap-2">
            <!-- Selection Dropdown -->
            <div class="dropdown">
                <button class="btn btn-dark btn-sm px-3 shadow-none fw-bold rounded-0 dropdown-toggle" style="font-size: 0.7rem;" type="button" data-bs-toggle="dropdown">
                    <i data-feather="check-square" class="me-1" style="width: 14px;"></i> PILIH DATA
                </button>
                <ul class="dropdown-menu rounded-0 shadow border-0" style="font-size: 0.7rem;">
                    <li><a class="dropdown-item" href="#" onclick="selectAll(true)">Select All (Semua)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="selectStatus('kirim')">Select Pending (Kirim)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="selectStatus('update')">Select Sent (Update)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="selectAll(false)">Batalkan Pilihan</a></li>
                </ul>
            </div>

            <!-- Batch Buttons -->
            <button class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0" id="btnBatchKirim" style="font-size: 0.7rem;">
                <i data-feather="send" class="me-2" style="width: 14px;"></i> KIRIM TERPILIH
            </button>
            <button class="btn btn-info btn-sm px-3 shadow-none fw-bold rounded-0 text-white" id="btnBatchUpdate" style="font-size: 0.7rem;">
                <i data-feather="refresh-cw" class="me-2" style="width: 14px;"></i> UPDATE TERPILIH
            </button>

            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" onclick="togglePrivacyMode()" style="font-size: 0.7rem;" title="Toggle Privacy Mode">
                <i data-feather="eye" class="me-2" style="width: 14px;"></i> PRIVACY
            </button>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.kirim-vaksin') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-2 col-6">
                        <label class="x-small fw-bold text-muted mb-1 d-block">MULAI (TGL BERI)</label>
                        <input type="date" name="tgl1" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" 
                               value="{{ $tgl1 }}" style="font-size: 0.65rem; height: 32px; background: #fff;">
                    </div>
                    <div class="col-md-2 col-6 border-start border-white">
                        <label class="x-small fw-bold text-muted mb-1 d-block">SAMPAI</label>
                        <input type="date" name="tgl2" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" 
                               value="{{ $tgl2 }}" style="font-size: 0.65rem; height: 32px; background: #fff;">
                    </div>
                    <div class="col-md-2 col-12 border-start border-white">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PER HALAMAN</label>
                        <select name="per_page" class="form-select form-select-sm rounded-0 border-0 shadow-none px-2" style="font-size: 0.65rem; height: 32px; background: #fff;">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 DATA</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 DATA</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 DATA</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 DATA</option>
                            <option value="all" {{ $perPage > 500 ? 'selected' : '' }}>SEMUA DATA</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-12 border-start border-white ps-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                style="width: 12px;"></i>
                            <input type="text" name="keyword" class="form-control form-control-sm ps-4 rounded-0 border-0 shadow-none"
                                placeholder="RM / No. Rawat / Pasien / Vaksin..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px; background: #fff;">
                        </div>
                    </div>
                    <div class="col-md-2 col-12 align-self-end">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 w-100 py-1" style="font-size: 0.6rem; height: 32px;">
                            TAMPILKAN DATA
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen table-hover mb-0">
                <thead>
                    <tr>
                        <th width="3%" class="text-center align-middle">
                            <input type="checkbox" id="checkAll" class="form-check-input shadow-none rounded-0">
                        </th>
                        <th width="25%">PASIEN & ENCOUNTER</th>
                        <th width="30%">VAKSIN & NO. BATCH</th>
                        <th width="12%" class="text-center">TGL/JAM BERI</th>
                        <th width="20%" class="text-center">ID IMMUNIZATION</th>
                        <th width="10%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $row)
                        @php $payload = json_encode($row); @endphp
                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-check-input check-item shadow-none rounded-0" 
                                       data-row='{{ $payload }}'
                                       data-status="{{ $row->id_vaksin ? 'update' : 'kirim' }}">
                            </td>
                            <td class="align-middle">
                                <div class="fw-bold text-slate-800" style="font-size: 0.7rem;">
                                    <span class="privacy-mask peekable">{{ strtoupper($row->nm_pasien) }}</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.55rem;">
                                    RM: {{ $row->no_rkm_medis }} | NIK: <span class="privacy-mask peekable">{{ $row->no_ktp_pasien ?: '-' }}</span>
                                </div>
                                <div class="text-muted italic mt-1" style="font-size: 0.55rem;">Encounter: {{ $row->id_encounter ?: 'ERR: Missing' }}</div>
                            </td>
                            <td class="align-middle">
                                <div class="fw-bold text-emerald-700" style="font-size: 0.65rem;">{{ $row->vaksin_display }}</div>
                                <div class="text-muted" style="font-size: 0.55rem;">Batch: {{ $row->no_batch ?: '-' }} | Exp: {{ $row->expire ?: '-' }}</div>
                                <div class="text-info" style="font-size: 0.55rem;">{{ $row->route_display }} | {{ $row->dose_quantity_unit }}</div>
                            </td>
                            <td class="text-center align-middle">
                                <div class="small fw-bold">{{ $row->tgl_perawatan }}</div>
                                <div class="text-muted x-small">{{ $row->jam }}</div>
                            </td>
                            <td class="text-center align-middle">
                                @if($row->id_vaksin)
                                    <code class="text-emerald fw-bold shadow-sm px-2 py-1" style="font-size: 0.6rem; background: #ecfdf5;">{{ $row->id_vaksin }}</code>
                                @else
                                    <span class="text-muted x-small italic opacity-50">Belum Terkirim</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn {{ $row->id_vaksin ? 'btn-info' : 'btn-emerald' }} btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white font-monospace" 
                                        onclick="sendVaksin(this)" 
                                        data-row='{{ $payload }}'
                                        style="font-size: 0.6rem;">
                                    {{ $row->id_vaksin ? 'UPDATE' : 'KIRIM' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i data-feather="slash" class="text-muted mb-2" style="width: 32px; height: 32px;"></i>
                                <div class="text-muted small">TIDAK ADA DATA VAKSINASI YANG DITEMUKAN</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->total() > 0)
        <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
            <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                SHOWING {{ $orders->firstItem() }}-{{ $orders->lastItem() }} OF {{ $orders->total() }} DATA
            </div>
            @if($orders->hasPages())
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        {{ $orders->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </ul>
                </nav>
            @endif
        </div>
    @endif
</div>

<!-- Log Modal -->
<div class="modal fade medizen-modal-minimal shadow-lg" id="modalLog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-0">
            <div class="modal-header py-2 px-3 bg-dark">
                <h6 class="modal-title px-0 text-white">SYSTEM LOG: IMMUNIZATION</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="log-content" class="bg-black text-emerald font-monospace p-3" 
                     style="font-size: 0.65rem; height: 350px; overflow-y: auto; line-height: 1.6;">
                </div>
            </div>
            <div class="modal-footer py-1 px-3 bg-dark border-top border-secondary text-end rounded-0">
                <button type="button" class="btn btn-outline-light btn-xs rounded-0 fw-bold px-3 shadow-none" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const appendLog = (type, msg) => {
        let color = 'text-white';
        if (type === 'ok') color = 'text-emerald fw-bold';
        if (type === 'err') color = 'text-danger fw-bold';
        if (type === 'info') color = 'text-info';
        $('#log-content').append(`<div><span class="opacity-50">[${new Date().toLocaleTimeString()}]</span> <span class="${color}">${msg}</span></div>`);
        $('#log-content').scrollTop($('#log-content')[0].scrollHeight);
    };

    // --- Selection Logic ---
    function selectAll(state) {
        $('.check-item').prop('checked', state).trigger('change');
        $('#checkAll').prop('checked', state);
    }

    function selectStatus(status) {
        $('.check-item').prop('checked', false);
        $(`.check-item[data-status="${status}"]`).prop('checked', true).trigger('change');
    }

    $('#checkAll').change(function() {
        $('.check-item').prop('checked', this.checked).trigger('change');
    });

    $('.check-item').change(function() {
        // Individual logic removed to keep buttons always visible or semi-active
    });

    // Individual Send
    async function sendVaksin(btn) {
        const row = $(btn).data('row');
        await doProcess([row]);
    }

    // Batch Send
    $('#btnBatchKirim').click(async function() {
        const selected = $('.check-item:checked').filter('[data-status="kirim"]').map(function() {
            return $(this).data('row');
        }).get();
        if (selected.length === 0) return Swal.fire('Info', 'Pilih data dengan status KIRIM!', 'info');
        await doProcess(selected);
    });

    // Batch Update
    $('#btnBatchUpdate').click(async function() {
        const selected = $('.check-item:checked').filter('[data-status="update"]').map(function() {
            return $(this).data('row');
        }).get();
        if (selected.length === 0) return Swal.fire('Info', 'Pilih data yang sudah SENT untuk diupdate!', 'info');
        await doProcess(selected);
    });

    async function doProcess(rows) {
        const modalLog = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty();
        modalLog.show();

        for (const row of rows) {
            try {
                appendLog('info', `PROSES: ${row.no_rawat} - ${row.vaksin_display}`);
                
                const res = await $.post('{{ route("satusehat.kirim-vaksin.post") }}', {
                    _token: '{{ csrf_token() }}',
                    ...row
                });

                if (res.ok) {
                    appendLog('ok', `SUKSES: FHIR ID ${res.id_vaksin}`);
                } else {
                    appendLog('err', `GAGAL: ${res.msg}`);
                }
            } catch (e) {
                appendLog('err', `FATAL ERROR: ${e.responseText}`);
            }
            appendLog('info', '--------------------------------------------------');
        }

        appendLog('ok', 'PROSES SELESAI.');
        // Swal.fire({ title: 'Selesai', icon: 'success' }).then(() => location.reload());
    }
</script>
@endpush
