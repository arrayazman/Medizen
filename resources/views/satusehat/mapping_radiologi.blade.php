@extends('layouts.app')

@section('page-title', 'Mapping Radiologi SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Tindakan Radiologi</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING JENIS PEMERIKSAAN RADIOLOGI KE LOINC & SNOMED</div>
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
            <form action="{{ route('satusehat.mapping-radiologi') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" placeholder="Nama Tindakan / Kode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
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
                        <th width="30%">NAMA PEMERIKSAAN</th>
                        <th width="25%">LOINC CODE / DISPLAY</th>
                        <th width="20%">SAMPLE / SPECIMEN</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="align-middle font-monospace small">{{ $row->kd_jenis_prw }}</td>
                            <td class="align-middle fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->nm_perawatan }}</td>
                            <td class="align-middle">
                                @if($row->code)
                                    <div class="fw-bold text-emerald-700" style="font-size: 0.65rem;">{{ $row->display }}</div>
                                    <div class="text-muted font-monospace" style="font-size: 0.6rem;">LOINC: {{ $row->code }}</div>
                                @else
                                    <span class="text-danger x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($row->sampel_code)
                                    <div class="text-info fw-bold" style="font-size: 0.6rem;">{{ $row->sampel_display }}</div>
                                    <div class="text-muted x-small">CODE: {{ $row->sampel_code }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="editMapping({{ json_encode($row) }})" style="font-size: 0.6rem;">MAPPING</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5">TIDAK ADA DATA PEMERIKSAAN RADIOLOGI</td></tr>
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
                <input type="hidden" name="kd_jenis_prw" id="edit_id">
                <div class="modal-header py-2 px-3 bg-dark text-white"><h6 class="modal-title">MAPPING RADIOLOGI LOINC</h6></div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="x-small fw-bold text-muted">PEMERIKSAAN SIMRS</label><input type="text" id="edit_name" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly></div>
                        
                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm h-100">
                                <div class="x-small fw-bold text-emerald mb-2">LOINC SPECIFICATION</div>
                                <label class="x-small text-muted">LOINC CODE</label><input type="text" name="code" id="edit_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">LOINC DISPLAY</label><input type="text" name="display" id="edit_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">SYSTEM</label><input type="text" name="system" id="edit_system" class="form-control form-control-sm rounded-0 border-bottom px-0" value="http://loinc.org">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white border shadow-sm h-100">
                                <div class="x-small fw-bold text-info mb-2">SAMPLE / SPECIMEN</div>
                                <label class="x-small text-muted">SAMPLE CODE</label><input type="text" name="sampel_code" id="edit_sampel_code" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">SAMPLE DISPLAY</label><input type="text" name="sampel_display" id="edit_sampel_display" class="form-control form-control-sm rounded-0 border-bottom mb-2 px-0">
                                <label class="x-small text-muted">SYSTEM</label><input type="text" name="sampel_system" id="edit_sampel_system" class="form-control form-control-sm rounded-0 border-bottom px-0" value="http://snomed.info/sct">
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
        $('#edit_id').val(row.kd_jenis_prw);
        $('#edit_name').val(row.nm_perawatan);
        $('#edit_code').val(row.code || '');
        $('#edit_display').val(row.display || '');
        $('#edit_system').val(row.system || 'http://loinc.org');
        $('#edit_sampel_code').val(row.sampel_code || '');
        $('#edit_sampel_display').val(row.sampel_display || '');
        $('#edit_sampel_system').val(row.sampel_system || 'http://snomed.info/sct');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-radiologi.store") }}', $(this).serialize(), function(res) {
            if (res.ok) Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
        });
    });
</script>
@endpush
