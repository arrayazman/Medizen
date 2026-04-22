@extends('layouts.app')

@section('title', 'SIMRS Mapping')
@section('page-title', 'Mapping Kode SIMRS')

@section('content')
    <div class="row g-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Daftar Pemetaan Kode SIMRS Khanza</h6>
                    <button type="button" class="btn btn-emerald btn-sm px-3" data-bs-toggle="modal"
                        data-bs-target="#addMappingModal">
                        <i data-feather="plus" class="me-1" style="width: 14px;"></i> TAMBAH MAPPING
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted uppercase small" style="letter-spacing: 0.5px;">
                                <tr>
                                    <th class="px-4 py-3">KODE PEMERIKSAAN SIMRS</th>
                                    <th class="py-3">MODALITAS RIS</th>
                                    <th class="py-3 text-end px-4">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mappings as $mapping)
                                    <tr>
                                        <td class="px-4 fw-bold text-primary">
                                            {{ $mapping->simrs_code }}
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary px-3 py-2">
                                                <i data-feather="monitor" class="me-1" style="width: 12px; height: 12px;"></i>
                                                {{ $mapping->modality->name ?? '-' }} ({{ $mapping->modality->code ?? '-' }})
                                            </span>
                                        </td>
                                        <td class="text-end px-4">
                                            <button class="btn btn-light btn-sm btn-icon" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $mapping->id }}">
                                                <i data-feather="edit-2" style="width: 14px;"></i>
                                            </button>
                                            <form action="{{ route('simrs-mappings.destroy', $mapping) }}" method="POST"
                                                class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm btn-icon text-danger"
                                                    onclick="return confirm('Hapus mapping ini?')">
                                                    <i data-feather="trash-2" style="width: 14px;"></i>
                                                </button>
                                            </form>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal{{ $mapping->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form action="{{ route('simrs-mappings.update', $mapping) }}" method="POST">
                                                        @csrf @method('PUT')
                                                        <div class="modal-content text-start">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Edit Mapping Modalitas</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">KODE SIMRS</label>
                                                                    <input type="text" class="form-control bg-light"
                                                                        value="{{ $mapping->simrs_code }}" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">MODALITAS
                                                                        RIS</label>
                                                                    <select name="modality_id" class="form-select" required>
                                                                        @foreach($modalities as $mod)
                                                                            <option value="{{ $mod->id }}" {{ $mapping->modality_id == $mod->id ? 'selected' : '' }}>
                                                                                {{ $mod->name }} ({{ $mod->code }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light px-4"
                                                                    data-bs-dismiss="modal">BATAL</button>
                                                                <button type="submit" class="btn btn-emerald px-4">SIMPAN
                                                                    PERUBAHAN</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted small italic">
                                            Belum ada data mapping. Pilih tombol "TAMBAH MAPPING" untuk menghubungkan tindakan
                                            SIMRS.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addMappingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('simrs-mappings.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Tambah Mapping Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase">Pilih Kode & Nama Pemeriksaan
                                SIMRS</label>
                            <select name="simrs_code" id="simrs_code_select" class="form-select select2-modal"
                                data-placeholder="Cari kd_jenis_prw atau nm_perawatan..." required>
                                <option value=""></option>
                                @foreach($availableTreatments as $treatment)
                                    <option value="{{ $treatment->kd_jenis_prw }}">
                                        [{{ $treatment->kd_jenis_prw }}] {{ $treatment->nm_perawatan }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text mt-2 italic small" style="font-size: 11px;">
                                * Menampilkan data <strong>jns_perawatan_radiologi</strong> dari Khanza yang belum
                                dipetakan.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase">Modalitas RIS</label>
                            <select name="modality_id" class="form-select select2-modal"
                                data-placeholder="Pilih Modalitas..." required>
                                <option value=""></option>
                                @foreach($modalities as $mod)
                                    <option value="{{ $mod->id }}">{{ $mod->name }} ({{ $mod->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-emerald px-4">TAMBAHKAN MAPPING</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <style>
            .select2-container--bootstrap-5 .select2-selection {
                border-radius: 0.5rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script src="{{ asset('js/select2.min.js') }}"></script>
        <script>
            $(document).ready(function () {
                $('.select2-modal').each(function () {
                    var dropdownParent = $(this).closest('.modal');
                    $(this).select2({
                        theme: 'bootstrap-5',
                        dropdownParent: dropdownParent,
                        width: '100%'
                    });
                });
            });
        </script>
    @endpush
@endsection