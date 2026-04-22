@extends('layouts.app')

@section('page-title', 'Mapping Lokasi SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Lokasi (Location)</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING POLIKLINIK & BANGSAL KE ID LOKASI SATUSEHAT</div>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group shadow-none border" role="group">
                <a href="{{ route('satusehat.mapping-lokasi', ['type' => 'poli']) }}" class="btn btn-sm {{ $type == 'poli' ? 'btn-dark' : 'btn-white text-muted' }} rounded-0 px-3 fw-bold" style="font-size: 0.65rem;">POLI / RALAN</a>
                <a href="{{ route('satusehat.mapping-lokasi', ['type' => 'bangsal']) }}" class="btn btn-sm {{ $type == 'bangsal' ? 'btn-dark' : 'btn-white text-muted' }} rounded-0 px-3 fw-bold" style="font-size: 0.65rem;">BANGSAL / RANAP</a>
            </div>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.mapping-lokasi') }}" method="GET" class="p-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="row g-1">
                    <div class="col-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0 border-0 shadow-none px-2" placeholder="Nama Unit / Kode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px;">
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
                        <th width="15%">KODE {{ strtoupper($type) }}</th>
                        <th width="35%">NAMA {{ strtoupper($type) }}</th>
                        <th width="35%">ID LOKASI (SATUSEHAT)</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="align-middle font-monospace small">{{ $row->kode }}</td>
                            <td class="align-middle fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->nama }}</td>
                            <td class="align-middle">
                                @if($row->id_fhir)
                                    <code class="text-emerald fw-bold px-2 py-1" style="font-size: 0.65rem; background: #ecfdf5;">{{ $row->id_fhir }}</code>
                                @else
                                    <span class="text-muted x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" onclick="editMapping('{{ $row->kode }}', '{{ $row->nama }}', '{{ $row->id_fhir }}')" style="font-size: 0.6rem;">MAPPING</button>
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
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="kode" id="edit_kode">
                <div class="modal-header py-2 px-3 bg-dark text-white"><h6 class="modal-title">MAPPING LOKASI FHIR</h6></div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="mb-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">UNIT / LOKASI SIMRS</label>
                        <input type="text" id="edit_nama" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">ID LOKASI SATUSEHAT</label>
                        <input type="text" name="id_fhir" id="edit_fhir" class="form-control form-control-sm rounded-0 border-bottom bg-white shadow-sm" placeholder="Paste Location ID here..." required>
                        <div class="text-muted x-small mt-1 italic">Contoh: 1000001-2301-4433-01</div>
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
    function editMapping(kode, nama, fhir) {
        $('#edit_kode').val(kode);
        $('#edit_nama').val(nama);
        $('#edit_fhir').val(fhir || '');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-lokasi.store") }}', $(this).serialize(), function(res) {
            if (res.ok) Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
        });
    });
</script>
@endpush
