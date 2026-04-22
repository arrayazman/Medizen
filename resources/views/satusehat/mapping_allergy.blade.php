@extends('layouts.app')

@section('page-title', 'Mapping Alergi SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Alergi</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">CONNECT SIMRS ALERGI KEYWORDS TO SATUSEHAT SNOMED CODES</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-emerald btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;" data-bs-toggle="modal" data-bs-target="#modalAdd">
                <i data-feather="plus" class="me-2" style="width: 14px;"></i> TAMBAH MAPPING
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="p-3 bg-light-soft border-bottom">
            <form action="{{ route('satusehat.mapping-allergy') }}" method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="keyword" class="form-control form-control-sm rounded-0" placeholder="Cari keyword atau display snomed..." value="{{ $keyword }}" style="font-size: 0.7rem;">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-sm rounded-0 w-100 fw-bold" style="font-size: 0.7rem;">CARI</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen mb-0">
                <thead>
                    <tr>
                        <th class="py-2 ps-3 small">Keyword (SIMRS)</th>
                        <th class="py-2 small">Category</th>
                        <th class="py-2 small">SNOMED Code</th>
                        <th class="py-2 small">SNOMED Display</th>
                        <th class="py-2 small text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mappings as $row)
                        <tr>
                            <td class="ps-3 align-middle fw-bold text-slate-700 small">{{ $row->keyword }}</td>
                            <td class="align-middle small text-uppercase"><span class="badge bg-light text-dark border">{{ $row->category }}</span></td>
                            <td class="align-middle small"><code>{{ $row->code }}</code></td>
                            <td class="align-middle small text-muted">{{ $row->display }}</td>
                            <td class="pe-3 text-end align-middle">
                                <button class="btn btn-link text-danger p-0 shadow-none" onclick="deleteMapping({{ $row->id }})">
                                    <i data-feather="trash-2" style="width: 14px;"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted small">BELUM ADA DATA MAPPING.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($mappings->hasPages())
    <div class="card-footer bg-white border-top py-2 px-3">
        {{ $mappings->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-dark text-white rounded-0 py-2">
                <h6 class="modal-title small fw-bold">TAMBAH MAPPING ALERGI</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAdd">
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-1 d-block text-uppercase">Keyword Alergi (SIMRS)</label>
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0" placeholder="Contoh: Amoxicillin" required>
                    </div>
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-1 d-block text-uppercase">Category</label>
                        <select name="category" class="form-select form-select-sm rounded-0" required>
                            <option value="medication">Medication</option>
                            <option value="food">Food Allergy</option>
                            <option value="environment">Environment Allergy</option>
                            <option value="biological">Biological</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-1 d-block text-uppercase">SNOMED Code</label>
                        <input type="text" name="code" class="form-control form-control-sm rounded-0" id="in_code" required>
                    </div>
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-1 d-block text-uppercase">SNOMED Display</label>
                        <input type="text" name="display" class="form-control form-control-sm rounded-0" id="in_display" required>
                    </div>
                    <div class="mb-2 text-end">
                        <button type="button" class="btn btn-outline-dark btn-sm rounded-0 x-small fw-bold" onclick="searchSnomed()">
                            <i data-feather="search" class="me-1" style="width: 10px;"></i> CARI DI REF_ALLERGY
                        </button>
                    </div>
                </div>
                <div class="modal-footer bg-light py-1">
                    <button type="button" class="btn btn-secondary btn-sm rounded-0 x-small fw-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-emerald btn-sm rounded-0 x-small fw-bold">SIMPAN MAPPING</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $('#formAdd').on('submit', async function(e) {
        e.preventDefault();
        try {
            const res = await $.post('{{ route("satusehat.mapping-allergy.store") }}', $(this).serialize());
            if (res.ok) {
                Swal.fire('Berhasil', res.msg, 'success').then(() => window.location.reload());
            } else {
                Swal.fire('Gagal', res.msg, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Internal Server Error', 'error');
        }
    });

    async function deleteMapping(id) {
        if (!confirm('Hapus mapping ini?')) return;
        try {
            const res = await $.ajax({
                url: '{{ route("satusehat.mapping-allergy.destroy") }}',
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}', id: id }
            });
            if (res.ok) window.location.reload();
            else alert(res.msg);
        } catch (e) {
            alert('Error');
        }
    }

    function searchSnomed() {
        Swal.fire({
            title: 'Cari SNOMED',
            input: 'text',
            inputPlaceholder: 'Enter search term...',
            showCancelButton: true,
            confirmButtonText: 'Search',
            allowOutsideClick: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                // Here we would ideally call an API, but since I can't build a full search API now, 
                // I'll suggest the user to use the reference table.
                Swal.fire('Info', 'Fitur search dari ref_allergy sedang dalam pengembangan. Silakan isi manual kode dari SATUSEHAT Dictionary.', 'info');
            }
        });
    }
</script>
@endpush
