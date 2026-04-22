@extends('layouts.app')

@section('page-title', 'Mapping Vaksin SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Vaksin</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING DATA VAKSIN KE KODIFIKASI KFA / HL7 SNOMED</div>
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
            <form action="{{ route('satusehat.mapping-vaksin') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" placeholder="Nama Vaksin / Kode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
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
                        <th width="25%">VAKSIN DISPLAY / CODE</th>
                        <th width="25%">DOSE & ROUTE</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="align-middle font-monospace small">{{ $row->kode_brng }}</td>
                            <td class="align-middle fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->nama_brng }}</td>
                            <td class="align-middle">
                                @if($row->vaksin_code)
                                    <div class="fw-bold text-emerald-700" style="font-size: 0.65rem;">{{ $row->vaksin_display }}</div>
                                    <div class="text-muted font-monospace" style="font-size: 0.6rem;">KFA: {{ $row->vaksin_code }}</div>
                                @else
                                    <span class="text-danger x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($row->vaksin_display)
                                    <div class="text-info fw-bold" style="font-size: 0.6rem;">{{ $row->route_display }}</div>
                                    <div class="text-muted x-small">Dose: {{ $row->dose_quantity_unit ?: '-' }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="editMapping({{ json_encode($row) }})" style="font-size: 0.6rem;">MAPPING</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5">TIDAK ADA DATA VAKSIN</td></tr>
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
                <div class="modal-header py-2 px-3 bg-dark text-white"><h6 class="modal-title">MAPPING VAKSIN KFA</h6></div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="x-small fw-bold text-muted">NAMA VAKSIN SIMRS</label><input type="text" id="edit_name" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly></div>
                        
                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm h-100">
                                <div class="x-small fw-bold text-emerald mb-2">VAKSIN IDENTIFIER</div>
                                <label class="x-small text-muted">VAKSIN CODE</label><input type="text" name="vaksin_code" id="edit_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">VAKSIN DISPLAY</label><input type="text" name="vaksin_display" id="edit_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">SYSTEM</label><input type="text" name="vaksin_system" id="edit_system" class="form-control form-control-sm rounded-0 border-bottom px-0" value="http://sys-ids.kemkes.go.id/kfa">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm h-100">
                                <div class="x-small fw-bold text-info mb-2">ROUTE & DOSAGE</div>
                                <label class="x-small text-muted">ROUTE CODE</label><input type="text" name="route_code" id="edit_route_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">ROUTE DISPLAY</label><input type="text" name="route_display" id="edit_route_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">DOSE UNIT</label><input type="text" name="dose_quantity_unit" id="edit_dose_unit" class="form-control form-control-sm rounded-0 border-bottom px-0">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-white border shadow-sm">
                                <div class="x-small fw-bold text-warning mb-2">UNIT SPECS</div>
                                <div class="row g-2">
                                    <div class="col-6"><label class="x-small text-muted">DOSE CODE</label><input type="text" name="dose_quantity_code" id="edit_dose_code" class="form-control form-control-sm rounded-0 border-bottom px-0"></div>
                                    <div class="col-6"><label class="x-small text-muted">DOSE SYSTEM</label><input type="text" name="dose_quantity_system" id="edit_dose_system" class="form-control form-control-sm rounded-0 border-bottom px-0" value="http://unitsofmeasure.org"></div>
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
        $('#edit_code').val(row.vaksin_code || '');
        $('#edit_display').val(row.vaksin_display || '');
        $('#edit_system').val(row.vaksin_system || 'http://sys-ids.kemkes.go.id/kfa');
        $('#edit_route_code').val(row.route_code || '');
        $('#edit_route_display').val(row.route_display || '');
        $('#edit_dose_unit').val(row.dose_quantity_unit || '');
        $('#edit_dose_code').val(row.dose_quantity_code || '');
        $('#edit_dose_system').val(row.dose_quantity_system || 'http://unitsofmeasure.org');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-vaksin.store") }}', $(this).serialize(), function(res) {
            if (res.ok) Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
        });
    });
</script>
@endpush
