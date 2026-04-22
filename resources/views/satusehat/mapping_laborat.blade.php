@extends('layouts.app')

@section('page-title', 'Mapping Laborat SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-2">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Tindakan Laborat</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">MAPPING TEMPLATE LABORATORIUM KE LOINC / SATUSEHAT CODE</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Integrated Filter Bar -->
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.mapping-laborat') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-3 col-12">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PER HALAMAN</label>
                        <select name="per_page" class="form-select form-select-sm rounded-0 border-0 shadow-none px-2" style="font-size: 0.65rem; height: 32px; background: #fff;">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 DATA</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 DATA</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 DATA</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 DATA</option>
                            <option value="all" {{ $perPage > 500 ? 'selected' : '' }}>SEMUA DATA</option>
                        </select>
                    </div>
                    <div class="col-md-7 col-12 border-start border-white ps-md-3">
                        <label class="x-small fw-bold text-muted mb-1 d-block">PENCARIAN</label>
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                style="width: 12px;"></i>
                            <input type="text" name="keyword" class="form-control form-control-sm ps-4 rounded-0 border-0 shadow-none"
                                placeholder="Cari Nama Pemeriksaan / ID / Code LOINC..." value="{{ $keyword }}" style="font-size: 0.65rem; height: 32px; background: #fff;">
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
                        <th width="5%" class="text-center">ID</th>
                        <th width="20%">PEMERIKSAAN (TEMPLATE)</th>
                        <th width="30%">LOINC / SATUSEHAT CODE</th>
                        <th width="30%">SAMPEL CODE</th>
                        <th width="15%" class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                        <tr>
                            <td class="text-center align-middle font-monospace small">{{ $row->id_template }}</td>
                            <td class="align-middle">
                                <div class="fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $row->Pemeriksaan }}</div>
                                <div class="text-muted" style="font-size: 0.55rem;">Jenis Prw: {{ $row->kd_jenis_prw }}</div>
                            </td>
                            <td class="align-middle">
                                @if($row->code)
                                    <div class="fw-bold text-emerald-700 font-monospace" style="font-size: 0.65rem;">{{ $row->code }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">{{ $row->display }}</div>
                                    <div class="x-small opacity-50 text-truncate" style="max-width: 250px;">{{ $row->system }}</div>
                                @else
                                    <span class="text-danger x-small italic opacity-50">Belum di-mapping</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($row->sampel_code)
                                    <div class="fw-bold text-info-700 font-monospace" style="font-size: 0.65rem;">{{ $row->sampel_code }}</div>
                                    <div class="text-muted" style="font-size: 0.6rem;">{{ $row->sampel_display }}</div>
                                    <div class="x-small opacity-50 text-truncate" style="max-width: 250px;">{{ $row->sampel_system }}</div>
                                @else
                                    <span class="text-muted x-small italic opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <button class="btn btn-emerald btn-xs px-3 rounded-0 fw-bold border-0 shadow-none text-white" 
                                        onclick="editMapping({{ json_encode($row) }})" 
                                        style="font-size: 0.6rem;">
                                    MAPPING
                                </button>
                                @if($row->code)
                                    <button class="btn btn-danger btn-xs px-2 rounded-0 fw-bold border-0 shadow-none text-white ms-1" 
                                            onclick="deleteMapping('{{ $row->id_template }}')" 
                                            style="font-size: 0.6rem;">
                                        <i data-feather="trash-2" style="width: 10px;"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i data-feather="layers" class="text-muted mb-2" style="width: 32px; height: 32px;"></i>
                                <div class="text-muted small">TIDAK ADA DATA PEMERIKSAAN LABORATORIUM</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Footer -->
    @if($mappings->total() > 0)
        <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
            <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                SHOWING {{ $mappings->firstItem() }}-{{ $mappings->lastItem() }} OF {{ $mappings->total() }} DATA
            </div>
            @if($mappings->hasPages())
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        {{ $mappings->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </ul>
                </nav>
            @endif
        </div>
    @endif
</div>

<!-- Edit Modal -->
<div class="modal fade medizen-modal-minimal rounded-0 shadow-lg" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-0">
            <form id="formMapping">
                @csrf
                <input type="hidden" name="id_template" id="edit_id">
                <div class="modal-header py-2 px-3 bg-dark">
                    <h6 class="modal-title px-0 text-white">MAPPING LABORATORIUM</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light-soft p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1 d-block">NAMA PEMERIKSAAN</label>
                        <input type="text" id="edit_name" class="form-control form-control-sm rounded-0 border-0 shadow-sm" readonly style="background: #f8f9fa;">
                    </div>
                    
                    <div class="p-3 bg-white border mb-3 shadow-sm">
                        <div class="x-small fw-bold text-emerald mb-2">LOINC / EXAMINATION CODE</div>
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="x-small text-muted mb-1">CODE</label>
                                <input type="text" name="code" id="edit_code" class="form-control form-control-sm rounded-0 shadow-none border-bottom" placeholder="Contoh: 123-4">
                            </div>
                            <div class="col-8">
                                <label class="x-small text-muted mb-1">SYSTEM</label>
                                <input type="text" name="system" id="edit_system" class="form-control form-control-sm rounded-0 shadow-none border-bottom" value="http://loinc.org">
                            </div>
                            <div class="col-12 mt-2">
                                <label class="x-small text-muted mb-1">DISPLAY NAME</label>
                                <input type="text" name="display" id="edit_display" class="form-control form-control-sm rounded-0 shadow-none border-bottom" placeholder="Contoh: Glucose [Mass/volume] in Blood">
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-white border shadow-sm">
                        <div class="x-small fw-bold text-info mb-2">SAMPEL CODE (SNOMED-CT)</div>
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="x-small text-muted mb-1">CODE</label>
                                <input type="text" name="sampel_code" id="edit_sampel_code" class="form-control form-control-sm rounded-0 shadow-none border-bottom" placeholder="Contoh: 119297000">
                            </div>
                            <div class="col-8">
                                <label class="x-small text-muted mb-1">SYSTEM</label>
                                <input type="text" name="sampel_system" id="edit_sampel_system" class="form-control form-control-sm rounded-0 shadow-none border-bottom" value="http://snomed.info/sct">
                            </div>
                            <div class="col-12 mt-2">
                                <label class="x-small text-muted mb-1">DISPLAY</label>
                                <input type="text" name="sampel_display" id="edit_sampel_display" class="form-control form-control-sm rounded-0 shadow-none border-bottom" placeholder="Contoh: Blood specimen">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-white border-top rounded-0">
                    <button type="button" class="btn btn-outline-dark btn-sm rounded-0 fw-bold px-4 shadow-none" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-emerald btn-sm rounded-0 fw-bold px-4 shadow-none">SIMPAN MAPPING</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editMapping(row) {
        $('#edit_id').val(row.id_template);
        $('#edit_name').val(row.Pemeriksaan);
        $('#edit_code').val(row.code || '');
        $('#edit_system').val(row.system || 'http://loinc.org');
        $('#edit_display').val(row.display || '');
        $('#edit_sampel_code').val(row.sampel_code || '');
        $('#edit_sampel_system').val(row.sampel_system || 'http://snomed.info/sct');
        $('#edit_sampel_display').val(row.sampel_display || '');
        
        const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
        modal.show();
    }

    $('#formMapping').submit(function(e) {
        e.preventDefault();
        $.post('{{ route("satusehat.mapping-laborat.store") }}', $(this).serialize(), function(res) {
            if (res.ok) {
                Swal.fire('Sukses', res.msg, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        });
    });

    function deleteMapping(id) {
        Swal.fire({
            title: 'Hapus Mapping?',
            text: "Data mapping akan dihapus dari sistem!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#374151',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("/satusehat/mapping-laborat") }}/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.ok) {
                            Swal.fire('Terhapus!', res.msg, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                        }
                    }
                });
            }
        });
    }
</script>
@endpush
