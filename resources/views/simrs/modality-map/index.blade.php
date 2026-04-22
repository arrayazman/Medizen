@extends('layouts.app')

@section('title', 'Mapping Modality SIMRS')
@section('page-title', 'Mapping Modality SIMRS')

@section('content')
    <div class="card card-medizen rounded-0 border-0 shadow-none">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0 py-3">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Mapping Modality SIMRS</h5>
                <div class="text-muted small" style="font-size: 0.65rem; letter-spacing: 0.5px;">MAPPING JNS_PERAWATAN_RADIOLOGI &rarr; MODALITY DICOM</div>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('simrs.modality-map.import') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0 d-flex align-items-center gap-2"
                        style="font-size: 0.7rem; border-width: 1.5px;"
                        onclick="return confirm('Import semua item dari SIMRS yang belum ada mapping-nya?')">
                        <i data-feather="download" style="width: 14px;"></i> IMPORT SIMRS
                    </button>
                </form>
                <button class="btn btn-dark btn-sm px-3 shadow-none fw-bold rounded-0 d-flex align-items-center gap-2" 
                    style="font-size: 0.7rem;"
                    data-bs-toggle="modal" data-bs-target="#addMapModal">
                    <i data-feather="plus" style="width: 14px;"></i> TAMBAH MANUAL
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filter Bar -->
            <div class="p-2 bg-light-soft border-bottom">
                <form action="{{ route('simrs.modality-map.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-2 text-muted"
                                style="width: 12px;"></i>
                            <input type="text" name="search" class="form-control form-control-sm ps-4 rounded-0 medizen-input-minimal"
                                placeholder="Cari kode / nama pemeriksaan..." value="{{ $search }}"
                                style="font-size: 0.7rem;">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 px-3 x-small">FILTER</button>
                        @if($search)
                            <a href="{{ route('simrs.modality-map.index') }}"
                                class="btn btn-light border btn-sm fw-bold rounded-0 px-3 x-small">RESET</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th class="py-2 small ps-3" style="width: 150px;">KODE SIMRS</th>
                            <th class="py-2 small">NAMA PEMERIKSAAN</th>
                            <th class="py-2 small text-center" style="width: 150px;">MODALITY</th>
                            <th class="py-2 small" style="width: 250px;">CATATAN</th>
                            <th class="py-2 small text-end pe-3" style="width: 120px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maps as $map)
                            <tr class="align-middle">
                                <td class="ps-3 py-2">
                                    <code class="fw-bold px-2 py-1 bg-light text-dark border-start border-3 border-dark" style="font-size: 0.7rem;">{{ $map->kd_jenis_prw }}</code>
                                </td>
                                <td class="py-2">
                                    <div class="fw-bolder text-slate-800" style="font-size: 0.75rem;">{{ strtoupper($map->nm_perawatan) ?: '-' }}</div>
                                </td>
                                <td class="py-2 text-center">
                                    <span class="badge rounded-0 bg-dark text-white px-2 py-1 fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">{{ $map->modality_code }}</span>
                                </td>
                                <td class="py-2">
                                    <div class="x-small text-muted text-truncate" style="max-width: 240px;" title="{{ $map->notes }}">{{ $map->notes ?: '—' }}</div>
                                </td>
                                <td class="py-2 text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-light border rounded-0 px-2 py-1 x-small fw-bold"
                                            onclick="openEditModal({{ $map->id }}, '{{ $map->kd_jenis_prw }}', '{{ addslashes($map->nm_perawatan) }}', '{{ $map->modality_code }}', '{{ addslashes($map->notes) }}')">
                                            EDIT
                                        </button>
                                        <form action="{{ route('simrs.modality-map.destroy', $map->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Hapus mapping ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-light border text-danger rounded-0 px-2 py-1 x-small fw-bold">HAPUS</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50 py-4">
                                        <i data-feather="map" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                                        <h6 class="fw-bold">BELUM ADA DATA MAPPING</h6>
                                        <p class="x-small">Gunakan fitur IMPORT untuk menyinkronkan data dari SIMRS.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($maps->hasPages())
                <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                    <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">
                        HALAMAN {{ $maps->currentPage() }} DARI {{ $maps->lastPage() }} | TOTAL: {{ $maps->total() }}
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            {{-- Previous --}}
                            <li class="page-item {{ $maps->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link rounded-0 border-0 fw-bold bg-transparent text-dark" href="{{ $maps->previousPageUrl() }}" style="font-size: 0.6rem;">‹ BEF</a>
                            </li>
                            
                            {{-- Pages --}}
                            @php $start = max(1, $maps->currentPage() - 1); $end = min($maps->lastPage(), $start + 2); @endphp
                            @for ($i = $start; $i <= $end; $i++)
                                <li class="page-item {{ $maps->currentPage() == $i ? 'active' : '' }}">
                                    <a class="page-link rounded-0 border-0 fw-bold {{ $maps->currentPage() == $i ? 'bg-dark text-white' : 'bg-transparent text-dark' }}" href="{{ $maps->url($i) }}" style="font-size: 0.6rem;">{{ $i }}</a>
                                </li>
                            @endfor

                            {{-- Next --}}
                            <li class="page-item {{ !$maps->hasMorePages() ? 'disabled' : '' }}">
                                <a class="page-link rounded-0 border-0 fw-bold bg-transparent text-dark" href="{{ $maps->nextPageUrl() }}" style="font-size: 0.6rem;">NEXT ›</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 bg-dark text-white py-2 rounded-0">
                    <h6 class="modal-title fw-bold">TAMBAH MAPPING SIMRS</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('simrs.modality-map.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">KODE JNS_PERAWATAN (KD_JENIS_PRW) *</label>
                            <input type="text" name="kd_jenis_prw" class="form-control form-control-sm rounded-0 medizen-input-minimal" placeholder="cth: 0001" required>
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">NAMA PEMERIKSAAN SIMRS</label>
                            <input type="text" name="nm_perawatan" class="form-control form-control-sm rounded-0 medizen-input-minimal" placeholder="cth: Thorax PA">
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">MODALITY CODE (DICOM) *</label>
                            <select name="modality_code" class="form-select form-select-sm rounded-0 medizen-input-minimal" required>
                                @foreach($modalityCodes as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="medizen-label-minimal mb-1">CATATAN TAMBAHAN</label>
                            <input type="text" name="notes" class="form-control form-control-sm rounded-0 medizen-input-minimal" placeholder="...">
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-2 bg-light-soft rounded-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 fw-bold x-small" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-sm btn-dark rounded-0 fw-bold x-small px-3">SIMPAN DATA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-0 shadow-none">
                <div class="modal-header border-0 bg-emerald text-white py-2 rounded-0">
                    <h6 class="modal-title fw-bold">EDIT MAPPING: <span id="editKodeText" class="opacity-75"></span></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMapForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">NAMA PEMERIKSAAN (RE-WRITE)</label>
                            <input type="text" name="nm_perawatan" id="editNmPerawatan" class="form-control form-control-sm rounded-0 medizen-input-minimal">
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">MODALITY CODE *</label>
                            <select name="modality_code" id="editModalityCode" class="form-select form-select-sm rounded-0 medizen-input-minimal" required>
                                @foreach($modalityCodes as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal mb-1">CATATAN</label>
                            <input type="text" name="notes" id="editNotes" class="form-control form-control-sm rounded-0 medizen-input-minimal">
                        </div>
                        
                        <div class="p-3 bg-light border-start border-3 border-emerald mt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_same_name" id="chkUpdateSameName" value="1" checked>
                                <label class="form-check-label fw-bold small text-dark" for="chkUpdateSameName" style="cursor: pointer;">
                                    UPDATE SEMUA NAMA YANG SAMA
                                </label>
                            </div>
                            <div class="text-muted x-small mt-1 ps-4">
                                Jika dicentang, semua kode SIMRS lain dengan nama pemeriksaan serupa akan ikut diperbarui modality-nya.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-2 bg-light-soft rounded-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-0 fw-bold x-small" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-sm btn-emerald text-white rounded-0 fw-bold x-small px-3">SIMPAN PERUBAHAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openEditModal(id, kode, nm, modality, notes) {
        document.getElementById('editKodeText').innerText = kode;
        document.getElementById('editNmPerawatan').value = nm;
        document.getElementById('editModalityCode').value = modality;
        document.getElementById('editNotes').value = notes;
        document.getElementById('editMapForm').action = `/simrs/modality-map/${id}`;

        new bootstrap.Modal(document.getElementById('editMapModal')).show();
    }

    $(document).ready(function() {
        feather.replace();
    });
</script>
@endpush
