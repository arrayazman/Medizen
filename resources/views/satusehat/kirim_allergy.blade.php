@extends('layouts.app')

@section('page-title', 'Bridging Allergy Intolerance SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Bridging Allergy Intolerance</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">SATUSEHAT INTEGRATION FOR PATIENT ALLERGIES</div>
        </div>
        <div class="d-flex gap-2">
            <button id="btnKirimBatch" class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;">
                <i data-feather="send" class="me-2" style="width: 14px;"></i> KIRIM TERPILIH
            </button>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.kirim-allergy') }}" method="GET" class="p-2">
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
                    <div class="col-md-6 col-12">
                        <label class="medizen-label-minimal mb-1" style="font-size: 0.55rem;">CARI PASIEN / RM / NO.RAWAT</label>
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
                        <th class="py-2 ps-3" style="width: 40px;">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </div>
                        </th>
                        <th class="py-2 small">Patient & Practitioner</th>
                        <th class="py-2 small">No. Rawat & Time</th>
                        <th class="py-2 small">Allergy Description</th>
                        <th class="py-2 small text-center">Status Lanjut</th>
                        <th class="py-2 small text-center">ID Allergy FHIR</th>
                        <th class="py-2 small text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="data-list">
                    @forelse ($orders as $row)
                        <tr>
                            <td class="ps-3 align-middle">
                                <div class="form-check mb-0">
                                    <input class="form-check-input row-check" type="checkbox" value="{{ json_encode($row) }}">
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800 small">{{ strtoupper($row->nm_pasien) }}</div>
                                <div class="text-muted" style="font-size: 0.6rem;">RM: {{ $row->no_rkm_medis }} | Oleh: {{ $row->nama_praktisi }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-700 small">{{ $row->no_rawat }}</div>
                                <div class="text-muted" style="font-size: 0.6rem;">{{ $row->tgl_perawatan }} {{ $row->jam_rawat }}</div>
                            </td>
                            <td>
                                <div class="text-danger fw-bold small">{{ $row->alergi }}</div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge {{ $row->status_lanjut === 'Ralan' ? 'bg-info' : 'bg-warning' }} x-small mb-1">{{ strtoupper($row->status_lanjut) }}</span>
                            </td>
                            <td class="text-center align-middle">
                                <code class="id-label x-small text-emerald fw-bold">{{ $row->id_allergy ?: '-' }}</code>
                            </td>
                            <td class="pe-3 text-end align-middle">
                                <button class="btn {{ $row->id_allergy ? 'btn-info' : 'btn-emerald' }} rounded-0 px-2 py-1 x-small fw-bold text-white border-0" 
                                        onclick='kirimSingle({{ json_encode($row) }}, this)'>
                                    {{ $row->id_allergy ? 'SYNC / UPDATE' : 'KIRIM DATA' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center opacity-50">
                                    <i data-feather="alert-circle" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                                    <h6 class="fw-bold small text-uppercase">TIDAK ADA DATA ALERGI</h6>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
            <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                SHOWING {{ $orders->firstItem() }}-{{ $orders->lastItem() }} OF {{ $orders->total() }}
            </div>
            {{ $orders->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>

<!-- Modal Log -->
<div class="modal fade medizen-modal-minimal" id="modalLog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title">LOG PENGIRIMAN ALLERGY</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="log-content" class="bg-dark text-emerald font-monospace p-3" style="font-size: 0.65rem; height: 350px; overflow-y: auto;">
                </div>
            </div>
            <div class="modal-footer bg-light py-1 px-3">
                <button type="button" class="btn btn-dark btn-sm rounded-0 fw-bold x-small" data-bs-dismiss="modal">CLOSE</button>
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

    async function kirimSingle(row, btn) {
        const modal = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty();
        modal.show();

        try {
            appendLog('info', `MENGIRIM DATA ALERGI: ${row.no_rawat} ...`);
            const res = await $.post('{{ route("satusehat.kirim-allergy.post") }}', {
                _token: '{{ csrf_token() }}',
                ...row
            });
            
            if (res.logs) res.logs.forEach(l => appendLog(l.type, l.msg.toUpperCase()));
            
            if (res.ok) {
                appendLog('ok', 'BERHASIL.');
                $(btn).closest('tr').find('.id-label').text(res.id_allergy);
            } else {
                appendLog('err', 'GAGAL: ' + (res.msg || 'UNKNOWN ERROR'));
            }
        } catch (xhr) {
            appendLog('err', 'FATAL ERROR: ' + xhr.responseText);
        }
    }

    $('#checkAll').on('change', function() {
        $('.row-check').prop('checked', this.checked);
    });

    $('#btnKirimBatch').on('click', async function() {
        const selected = $('.row-check:checked');
        if(selected.length === 0) return Swal.fire('Info', 'Pilih data terlebih dahulu!', 'info');
        
        const modal = new bootstrap.Modal(document.getElementById('modalLog'));
        $('#log-content').empty();
        modal.show();

        for(let i=0; i<selected.length; i++) {
            const row = JSON.parse($(selected[i]).val());
            appendLog('info', `BATCH [${i+1}/${selected.length}] : ${row.no_rawat}`);
            // Similar logic to kirimSingle but await here
            await new Promise(resolve => setTimeout(resolve, 500)); // Simulate
            appendLog('err', 'MODUL BRIDGING BELUM DIAKTIFKAN UNTUK BATCH.');
        }
    });
</script>
@endpush
