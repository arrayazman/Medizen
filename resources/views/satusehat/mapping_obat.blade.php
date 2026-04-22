@extends('layouts.app')

@section('page-title', 'Mapping Obat SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Obat & Alkes</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING DATA BARANG KE KODIFIKASI KFA / SATUSEHAT</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.mapping-obat') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" placeholder="Nama Obat / Kode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 w-100 py-1" style="font-size: 0.6rem; height: 32px;">TAMPILKAN</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen table-hover mb-0">
                <thead>
                    <tr>
                        <th width="10%">KODE</th>
                        <th width="25%">NAMA BARANG</th>
                        <th width="25%">KFA DISPLAY / CODE</th>
                        <th width="25%">DOSAGE & ROUTE</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="align-middle font-monospace small">{{ $row->kode_brng }}</td>
                            <td class="align-middle fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->nama_brng }}</td>
                            <td class="align-middle">
                                @if($row->obat_code)
                                    <div class="fw-bold text-emerald-700" style="font-size: 0.65rem;">{{ $row->obat_display }}</div>
                                    <div class="text-muted font-monospace" style="font-size: 0.6rem;">KFA: {{ $row->obat_code }}</div>
                                @else
                                    <span class="text-danger x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($row->form_display)
                                    <div class="text-info fw-bold" style="font-size: 0.6rem;">{{ $row->form_display }} ({{ $row->route_display }})</div>
                                    <div class="text-muted x-small">Denominator: {{ $row->denominator_code }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="editMapping({{ json_encode($row) }})" style="font-size: 0.6rem;">MAPPING</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5">TIDAK ADA DATA BARANG</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade medizen-modal-minimal rounded-0 shadow-lg" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-0">
            <form id="formMapping">
                @csrf
                <input type="hidden" name="kode_brng" id="edit_id">
                <div class="modal-header py-2 px-3 bg-dark text-white"><h6 class="modal-title">MAPPING OBAT KFA</h6></div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="x-small fw-bold text-muted">NAMA BARANG SIMRS</label><input type="text" id="edit_name" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly></div>
                        
                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm">
                                <div class="x-small fw-bold text-emerald mb-2">KFA IDENTIFIER</div>
                                <label class="x-small text-muted">KFA CODE</label><input type="text" name="obat_code" id="edit_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">KFA DISPLAY</label><input type="text" name="obat_display" id="edit_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">SYSTEM</label><input type="text" name="obat_system" id="edit_system" class="form-control form-control-sm rounded-0 border-bottom px-0" value="http://sys-ids.kemkes.go.id/kfa">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm">
                                <div class="x-small fw-bold text-info mb-2">FORM & ROUTE</div>
                                <label class="x-small text-muted">FORM CODE</label><input type="text" name="form_code" id="edit_form_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">FORM DISPLAY</label><input type="text" name="form_display" id="edit_form_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">ROUTE CODE</label><input type="text" name="route_code" id="edit_route_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">ROUTE DISPLAY</label><input type="text" name="route_display" id="edit_route_display" class="form-control form-control-sm rounded-0 border-bottom px-0">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-white border shadow-sm">
                                <div class="x-small fw-bold text-warning mb-2">UNIT / DENOMINATOR</div>
                                <div class="row g-2">
                                    <div class="col-6"><label class="x-small text-muted">DENOMINATOR CODE</label><input type="text" name="denominator_code" id="edit_denom_code" class="form-control form-control-sm rounded-0 border-bottom px-0" placeholder="Contoh: TAB"></div>
                                    <div class="col-6"><label class="x-small text-muted">NUMERATOR CODE</label><input type="text" name="numerator_code" id="edit_num_code" class="form-control form-control-sm rounded-0 border-bottom px-0" value="1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-emerald btn-sm rounded-0 fw-bold px-4">SIMPAN MAPPING</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editMapping(row) {
        $('#edit_id').val(row.kode_brng);
        $('#edit_name').val(row.nama_brng);
        $('#edit_code').val(row.obat_code || '');
        $('#edit_display').val(row.obat_display || '');
        $('#edit_system').val(row.obat_system || 'http://sys-ids.kemkes.go.id/kfa');
        $('#edit_form_code').val(row.form_code || '');
        $('#edit_form_display').val(row.form_display || '');
        $('#edit_route_code').val(row.route_code || '');
        $('#edit_route_display').val(row.route_display || '');
        $('#edit_denom_code').val(row.denominator_code || '');
        $('#edit_num_code').val(row.numerator_code || '1');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-obat.store") }}', $(this).serialize(), function(res) {
            if (res.ok) Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
        });
    });
</script>
@endpush
