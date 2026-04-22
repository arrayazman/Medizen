@extends('layouts.app')

@section('page-title', 'Kirim Observation TTV SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Kirim Tanda Vital (TTV)</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">BRIDGING SUHU, NADI, TENSI, RESPIRASI, DLL KE SATUSEHAT Observation Resource</div>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-dark btn-sm px-3 shadow-none fw-bold rounded-0 dropdown-toggle" style="font-size: 0.7rem;" type="button" data-bs-toggle="dropdown">
                    <i data-feather="check-square" class="me-1" style="width: 14px;"></i> PILIH DATA
                </button>
                <ul class="dropdown-menu dropdown-menu-end rounded-0 shadow border-0" style="font-size: 0.7rem;">
                    <li><a class="dropdown-item" href="#" onclick="selectAll(true)">Select All</a></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="selectAll(false)">Batalkan</a></li>
                </ul>
            </div>

            <button class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0" id="btnBatchKirim" style="font-size: 0.7rem;">
                <i data-feather="send" class="me-1" style="width: 14px;"></i> KIRIM TERPILIH
            </button>

            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" onclick="togglePrivacyMode()" style="font-size: 0.7rem;">
                <i data-feather="eye" class="me-2" style="width: 14px;"></i> PRIVACY
            </button>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.kirim-observation-ttv') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-2 col-6">
                        <label class="x-small fw-bold text-muted mb-1 d-block">MULAI</label>
                        <input type="date" name="tgl1" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" value="{{ $tgl1 }}" style="font-size: 0.65rem; height: 32px;">
                    </div>
                    <div class="col-md-2 col-6 border-start border-white">
                        <label class="x-small fw-bold text-muted mb-1 d-block">SAMPAI</label>
                        <input type="date" name="tgl2" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" value="{{ $tgl2 }}" style="font-size: 0.65rem; height: 32px;">
                    </div>
                    <div class="col-md-4 col-12 border-start border-white ps-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none" placeholder="No. Rawat / RM / Nama..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
                    </div>
                    <div class="col-md-2 col-6 border-start border-white">
                        <label class="x-small fw-bold text-muted mb-1 d-block">TAMPILKAN</label>
                        <select name="per_page" class="form-select form-select-sm rounded-0 border-0 shadow-none" style="font-size: 0.65rem; height: 32px;">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 Data</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 Data</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6 align-self-end">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 w-100 py-1" style="font-size: 0.6rem; height: 32px;">TAMPILKAN</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen table-hover mb-0">
                <thead>
                    <tr>
                        <th width="3%" class="text-center align-middle"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                        <th width="20%">PASIEN & ENCOUNTER</th>
                        <th width="15%">PENGUKURAN</th>
                        <th width="35%">HASIL TTV</th>
                        <th width="12%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $row)
                        @php 
                            $payload = json_encode($row); 
                            $rowId = 'row-' . str_replace(['/', '.'], '-', $row->no_rawat) . '-' . str_replace([':', ' '], '-', $row->tgl_perawatan . '-' . $row->jam_rawat);
                        @endphp
                        <tr id="{{ $rowId }}">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-check-input check-item" data-row='{{ $payload }}'>
                            </td>
                            <td class="align-middle">
                                <div class="fw-bold text-slate-800" style="font-size: 0.7rem;"><span class="privacy-mask peekable">{{ strtoupper($row->nm_pasien) }}</span></div>
                                <div class="text-muted" style="font-size: 0.55rem;">RM: {{ $row->no_rkm_medis }} | Nik: {{ $row->no_ktp_pasien ?: '-' }}</div>
                            </td>
                            <td class="align-middle">
                                <div class="fw-bold text-emerald-700" style="font-size: 0.65rem;">{{ $row->no_rawat }}</div>
                                <div class="text-muted" style="font-size: 0.55rem;">{{ $row->tgl_perawatan }} {{ $row->jam_rawat }}</div>
                            </td>
                            <td class="align-middle">
                                <div class="row g-1">
                                    <div class="col-3">
                                        <div class="x-small text-muted">SUHU</div>
                                        <div class="fw-bold row-suhu {{ $row->id_observation_suhu ? 'text-emerald' : 'text-slate-700' }}" style="font-size: 0.65rem;">
                                            {{ $row->suhu_tubuh ?: '-' }} °C @if($row->id_observation_suhu) <i data-feather="check" style="width:10px;"></i> @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="x-small text-muted">TENSI</div>
                                        <div class="fw-bold row-tensi {{ $row->id_observation_tensi ? 'text-emerald' : 'text-slate-700' }}" style="font-size: 0.65rem;">
                                            {{ $row->tensi ?: '-' }} mmHg @if($row->id_observation_tensi) <i data-feather="check" style="width:10px;"></i> @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="x-small text-muted">NADI</div>
                                        <div class="fw-bold row-nadi {{ $row->id_observation_nadi ? 'text-emerald' : 'text-slate-700' }}" style="font-size: 0.65rem;">
                                            {{ $row->nadi ?: '-' }} x/m @if($row->id_observation_nadi) <i data-feather="check" style="width:10px;"></i> @endif
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="x-small text-muted">RESP</div>
                                        <div class="fw-bold row-respirasi {{ $row->id_observation_respirasi ? 'text-emerald' : 'text-slate-700' }}" style="font-size: 0.65rem;">
                                            {{ $row->respirasi ?: '-' }} x/m @if($row->id_observation_respirasi) <i data-feather="check" style="width:10px;"></i> @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center align-middle action-cell">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="sendTTV(this)" data-row='{{ $payload }}' style="font-size: 0.6rem;">KIRIM TTV</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted small">TIDAK ADA DATA TTV</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Medizen-style Premium Modal Log -->
<div class="modal fade" id="modalLog" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-0 shadow-lg overflow-hidden" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
            <div class="modal-header py-3 px-4 bg-emerald text-white border-0 rounded-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-20 p-2 rounded-3 me-3">
                        <i data-feather="activity" class="text-white" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" style="letter-spacing: 0.5px;">PROSES TRANSMISI SATUSEHAT</h6>
                        <div class="x-small opacity-75">Observation (Tanda Vital) Synchronization...</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="log-content" class="p-4" style="font-size: 0.75rem; height: 400px; overflow-y: auto; background: #f8fafc; font-family: monospace;"></div>
            </div>
            <div class="modal-footer py-2 px-4 bg-white border-top border-light d-flex justify-content-between align-items-center">
                <div id="process-stats" class="small text-muted">Ready.</div>
                <button type="button" class="btn btn-dark btn-sm rounded-0 fw-bold px-4" data-bs-dismiss="modal">TUTUP LOG</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    #log-content div { margin-bottom: 4px; padding-left: 10px; border-left: 2px solid transparent; }
    .log-info { border-left-color: #94a3b8; color: #475569; }
    .log-success { border-left-color: #10b981; color: #059669; font-weight: 600; background: #f0fdf4; padding: 5px; }
    .log-error { border-left-color: #ef4444; color: #dc2626; font-weight: 600; background: #fef2f2; padding: 5px; }
</style>
<script>
    const appendLog = (type, msg) => {
        let cls = 'log-info';
        if (type === 'ok') cls = 'log-success';
        if (type === 'err') cls = 'log-error';
        $('#log-content').append(`<div class="${cls}"><span class="text-muted small me-2">${new Date().toLocaleTimeString()}</span> ${msg}</div>`);
        $('#log-content').scrollTop($('#log-content')[0].scrollHeight);
    };

    function selectAll(state) { $('.check-item').prop('checked', state); }

    async function sendTTV(btn) { await doProcess([$(btn).data('row')]); }
    
    $('#btnBatchKirim').click(async function() {
        const selected = $('.check-item:checked').map(function() { return $(this).data('row'); }).get();
        if (selected.length === 0) return Swal.fire('Info', 'Pilih data!', 'info');
        await doProcess(selected);
    });

    async function doProcess(rows) {
        const modalLog = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty(); modalLog.show();
        let successCount = 0;
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const rowId = 'row-' + row.no_rawat.replace(/[\/\.]/g, '-') + '-' + (row.tgl_perawatan + '-' + row.jam_rawat).replace(/[: ]/g, '-');
            const $targetRow = $('#' + rowId);
            $('#process-stats').text(`Processing ${i+1}/${rows.length}...`);
            appendLog('info', `PROSES TTV: ${row.no_rawat} - ${row.nm_pasien}`);
            try {
                const res = await $.post('{{ route("satusehat.kirim-observation-ttv.post") }}', { _token: '{{ csrf_token() }}', ...row });
                if (res.ok) {
                    successCount++;
                    appendLog('ok', `SUKSES MENGIRIM TTV.`);
                    // Update UI: change colors to emerald and add check marks
                    $targetRow.find('.row-suhu, .row-tensi, .row-nadi, .row-respirasi').addClass('text-emerald').removeClass('text-slate-700');
                    $targetRow.find('.check-item').prop('checked', false);
                } else {
                    appendLog('err', `GAGAL: ${res.msg}`);
                }
            } catch (e) { appendLog('err', `ERROR: ${e.statusText}`); }
        }
        $('#process-stats').text(`Done. ${successCount} Success.`);
        feather.replace();
    }
</script>
@endpush
