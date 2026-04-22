@extends('layouts.app')

@section('page-title', 'Mapping Organisasi SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Organisasi (Organization)</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING DEPARTEMEN / UNIT KERJA KE ID ORGANISASI SATUSEHAT</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.mapping-organisasi') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" placeholder="Nama Departemen / Kode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
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
                        <th width="15%">KODE DEPARTEMEN</th>
                        <th width="40%">NAMA DEPARTEMEN</th>
                        <th width="30%">ID ORGANISASI (SATUSEHAT)</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="align-middle font-monospace small">{{ $row->dep_id }}</td>
                            <td class="align-middle fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->nama }}</td>
                            <td class="align-middle">
                                @if($row->id_organisasi_satusehat)
                                    <code class="text-emerald fw-bold px-2 py-1" style="font-size: 0.65rem; background: #ecfdf5;">{{ $row->id_organisasi_satusehat }}</code>
                                @else
                                    <span class="text-muted x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="editMapping('{{ $row->dep_id }}', '{{ $row->nama }}', '{{ $row->id_organisasi_satusehat }}')" style="font-size: 0.6rem;">MAPPING</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-5">TIDAK ADA DATA</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade medizen-modal-minimal rounded-0 shadow-lg" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-0">
            <form id="formMapping">
                @csrf
                <input type="hidden" name="dep_id" id="edit_id">
                <div class="modal-header py-2 px-3 bg-dark text-white"><h6 class="modal-title">MAPPING ORGANISASI FHIR</h6></div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="mb-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">DEPARTEMEN SIMRS</label>
                        <input type="text" id="edit_nama" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">ID ORGANISASI SATUSEHAT</label>
                        <input type="text" name="id_fhir" id="edit_fhir" class="form-control form-control-sm rounded-0 border-bottom bg-white shadow-sm" placeholder="Paste Organization ID here..." required>
                        <div class="text-muted x-small mt-1 italic">Dapatkan dari dashboard SatuSehat atau hasil POST Organization.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="submit" class="btn btn-emerald btn-sm rounded-0 fw-bold px-4 shadow-none">SIMPAN MAPPING</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editMapping(id, nama, fhir) {
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#edit_fhir').val(fhir || '');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-organisasi.store") }}', $(this).serialize(), function(res) {
            if (res.ok) Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
        });
    });
</script>
@endpush
