@extends('layouts.app')

@section('page-title', 'Mapping Episode Of Care')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Episode Of Care</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING DIAGNOSA PENYAKIT KE TIPE EPISODE OF CARE (BERDASARKAN REFERENSI JAVA)</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-emerald btn-sm px-4 shadow-none fw-bold rounded-0" onclick="addMapping()" style="font-size: 0.7rem;">
                <i data-feather="plus" class="me-2" style="width: 14px;"></i> TAMBAH MAPPING
            </button>
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.mapping-episodeofcare') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-10">
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted" style="width: 12px;"></i>
                            <input type="text" name="keyword" class="form-control form-control-sm ps-4 rounded-0 border-0 shadow-none"
                                placeholder="Kode / Nama Penyakit / Tipe Episode..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px; background: #fff;">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 w-100 py-1" style="font-size: 0.6rem; height: 32px;">
                            CARI DATA
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen table-hover mb-0">
                <thead>
                    <tr>
                        <th width="15%">KODE ICD-10</th>
                        <th width="35%">NAMA PENYAKIT</th>
                        <th width="35%">TIPE EPISODE (SATUSEHAT)</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="fw-bold text-emerald-700">{{ $row->kd_penyakit }}</td>
                            <td style="font-size: 0.7rem; white-space: normal;">{{ $row->nm_penyakit }}</td>
                            <td>
                                <div class="fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->display_episode }}</div>
                                <div class="text-muted small" style="font-size: 0.55rem;">CODE: {{ $row->kode_episode }} | {{ $row->keterangan }}</div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-link text-info btn-sm p-0 me-3" onclick="editMapping('{{ $row->kd_penyakit }}', '{{ $row->nm_penyakit }}', '{{ $row->kode_episode }}')">
                                        <i data-feather="edit-2" style="width: 14px;"></i>
                                    </button>
                                    <button class="btn btn-link text-danger btn-sm p-0" onclick="deleteMapping('{{ $row->kd_penyakit }}')">
                                        <i data-feather="trash-2" style="width: 14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted small italic">TIDAK ADA DATA MAPPING DITEMUKAN</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($mappings->hasPages())
        <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
            <div class="text-muted fw-bold" style="font-size: 0.6rem;">
                SHOWING {{ $mappings->firstItem() }}-{{ $mappings->lastItem() }} OF {{ $mappings->total() }} DATA
            </div>
            <nav>{{ $mappings->appends(request()->query())->links('pagination::bootstrap-4') }}</nav>
        </div>
    @endif
</div>

<!-- Modal Form -->
<div class="modal fade medizen-modal-minimal" id="modalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 px-3 bg-dark text-white">
                <h6 class="modal-title">FORM MAPPING EPISODE</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formMapping">
                    @csrf
                    <div class="mb-3">
                        <label class="medizen-label-minimal mb-2">PENYAKIT (ICD-10)</label>
                        <select name="kd_penyakit" id="select-penyakit" class="form-control" required style="width: 100%"></select>
                    </div>
                    <div class="mb-3">
                        <label class="medizen-label-minimal mb-2">TIPE EPISODE OF CARE</label>
                        <select name="kode_episode" id="form-kode_episode" class="form-select form-select-sm rounded-0" required>
                            <option value="">-- Pilih Tipe Episode --</option>
                            @foreach($episodeTypes as $type)
                                <option value="{{ $type->kode }}">{{ $type->display }} ({{ $type->keterangan }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-emerald btn-sm w-100 fw-bold rounded-0" id="btnSubmit">SIMPAN DATA MAPPING</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#select-penyakit').select2({
            dropdownParent: $('#modalForm'),
            ajax: {
                url: '{{ route("satusehat.mapping-episodeofcare.search-penyakit") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return {
                        results: data.map(item => ({ id: item.kd_penyakit, text: `${item.kd_penyakit} - ${item.nm_penyakit}` }))
                    };
                }
            }
        });
    });

    function addMapping() {
        $('#formMapping')[0].reset();
        $('#select-penyakit').val(null).trigger('change');
        $('#modalForm').modal('show');
    }

    function editMapping(kd, nm, kode) {
        var option = new Option(`${kd} - ${nm}`, kd, true, true);
        $('#select-penyakit').append(option).trigger('change');
        $('#form-kode_episode').val(kode);
        $('#modalForm').modal('show');
    }

    $('#formMapping').submit(async function(e) {
        e.preventDefault();
        $('#btnSubmit').prop('disabled', true).text('SAVING...');
        try {
            const res = await $.post('{{ route("satusehat.mapping-episodeofcare.post") }}', $(this).serialize());
            if (res.ok) {
                Swal.fire('Berhasil', res.msg, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal', res.msg, 'error');
                $('#btnSubmit').prop('disabled', false).text('SIMPAN DATA MAPPING');
            }
        } catch (e) {
            Swal.fire('Error', 'Sistem error.', 'error');
            $('#btnSubmit').prop('disabled', false).text('SIMPAN DATA MAPPING');
        }
    });

    async function deleteMapping(kd) {
        const confirm = await Swal.fire({ title: 'Hapus?', text: 'Hapus mapping diagnosa ini?', icon: 'warning', showCancelButton: true });
        if (confirm.isConfirmed) {
            const res = await $.ajax({
                url: '{{ route("satusehat.mapping-episodeofcare.destroy") }}',
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}', kd_penyakit: kd }
            });
            if (res.ok) location.reload();
        }
    }
</script>
<style>
    .select2-container--default .select2-selection--single {
        border-radius: 0;
        height: 32px;
        border: 1px solid #dee2e6;
    }
</style>
@endpush
