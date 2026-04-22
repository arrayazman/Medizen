@extends('layouts.app')
@section('title', 'PACS Modalities')
@section('page-title', 'Modalitas PACS')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 14px; letter-spacing: 1px;">
            MANAJEMEN PERANGKAT PACS
        </div>
        <button type="button" class="btn btn-emerald medizen-btn-minimal" id="btnTambahModality">
            <i data-feather="plus" class="me-1" style="width: 14px;"></i> TAMBAH PERANGKAT
        </button>
    </div>

    <div class="medizen-card-minimal">
        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="20%">MODALITY ID</th>
                        <th width="15%">AE TITLE</th>
                        <th width="15%">HOST / IP</th>
                        <th width="10%" class="text-center">PORT</th>
                        <th width="30%">CAPABILITIES (E/F/G/S/M)</th>
                        <th width="10%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modalities as $mod)
                        <tr>
                            <td class="fw-bold text-slate-800">
                                {{ $mod['Name'] }}
                                @if($mod['Manufacturer'] ?? false)
                                    <div class="text-muted fw-normal" style="font-size: 10px;">
                                        {{ strtoupper($mod['Manufacturer']) }}</div>
                                @endif
                            </td>
                            <td><span class="text-emerald fw-bold">{{ $mod['AET'] ?? '-' }}</span></td>
                            <td class="font-monospace text-slate-600">{{ $mod['Host'] ?? '-' }}</td>
                            <td class="text-center font-monospace">{{ $mod['Port'] ?? '-' }}</td>
                            <td>
                                <span class="medizen-indicator {{ ($mod['AllowEcho'] ?? true) ? 'active' : '' }} me-1" title="C-ECHO">E</span>
                                <span class="medizen-indicator {{ ($mod['AllowFind'] ?? true) ? 'active' : '' }} me-1" title="C-FIND">F</span>
                                <span class="medizen-indicator {{ ($mod['AllowGet'] ?? true) ? 'active' : '' }} me-1" title="C-GET">G</span>
                                <span class="medizen-indicator {{ ($mod['AllowStore'] ?? true) ? 'active' : '' }} me-1" title="C-STORE">S</span>
                                <span class="medizen-indicator {{ ($mod['AllowMove'] ?? true) ? 'active' : '' }} me-1" title="C-MOVE">M</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button" class="btn btn-emerald-soft medizen-btn-action-minimal edit-modality"
                                        data-json="{{ json_encode($mod) }}">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </button>
                                    <form action="{{ route('pacs.destroy-modality', $mod['Name']) }}" method="POST"
                                        class="d-inline swal-confirm" data-swal-title="Remove Modality?" data-swal-text="Hapus konfigurasi perangkat {{ $mod['Name'] }} dari PACS server?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger-soft medizen-btn-action-minimal">
                                            <i data-feather="trash-2" style="width: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted small uppercase">TIDAK ADA PERANGKAT TERDAFTAR
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade medizen-modal-minimal" id="modalModality" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <span class="modal-title fw-bold" id="modalTitle">KONFIGURASI MODALITAS</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        style="font-size: 10px;"></button>
                </div>
                <form id="formModality" action="{{ route('pacs.store-modality') }}" method="POST">
                    @csrf
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="medizen-label-minimal">ID PERANGKAT (UNIQUE)</label>
                            <input type="text" name="Name" id="mod_Name" class="form-control medizen-input-minimal" placeholder="Contoh: CT_SCAN_01" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="medizen-label-minimal">AE TITLE</label>
                                <input type="text" name="AET" id="mod_AET" class="form-control medizen-input-minimal" placeholder="AE_TITLE" required>
                            </div>
                            <div class="col-4">
                                <label class="medizen-label-minimal">PORT</label>
                                <input type="number" name="Port" id="mod_Port" class="form-control medizen-input-minimal"
                                    value="104" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal">ALAMAT IP / HOST</label>
                            <input type="text" name="Host" id="mod_Host" class="form-control medizen-input-minimal" placeholder="192.168.1.xxx" required>
                        </div>
                        <div class="mb-3">
                            <label class="medizen-label-minimal">VENDOR PROFILE (INTEROPERABILITY PATCH)</label>
                            <select name="Manufacturer" id="mod_Manufacturer" class="form-select medizen-input-minimal">
                                <option value="Generic">Generic (Standard DICOM)</option>
                                <option value="GenericNoWildcardInDates">Generic (No wildcard in dates)</option>
                                <option value="GenericNoUniversalWildcard">Generic (No universal wildcard)</option>
                                <option value="StoreScp">StoreScp (DCMTK)</option>
                                <option value="ClearCanvas">ClearCanvas</option>
                                <option value="Dcm4Chee">Dcm4Chee</option>
                                <option value="Vitrea">Vitrea</option>
                            </select>
                            <small class="text-muted" style="font-size: 9px;">*Gunakan 'Generic' kecuali jika ada masalah koneksi spesifik dengan perangkat tersebut.</small>
                        </div>

                        <div class="p-3 bg-light-soft border border-dashed rounded-0">
                            <div class="medizen-label-minimal mb-2 text-emerald">DICOM CAPABILITIES / PERMISSIONS</div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check form-switch custom-switch-emerald">
                                        <input class="form-check-input" type="checkbox" name="AllowEcho" id="mod_AllowEcho"
                                            value="1" checked>
                                        <label class="form-check-label small fw-bold text-slate-600" for="mod_AllowEcho">C-ECHO (Ping)</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch custom-switch-emerald">
                                        <input class="form-check-input" type="checkbox" name="AllowFind" id="mod_AllowFind"
                                            value="1" checked>
                                        <label class="form-check-label small fw-bold text-slate-600" for="mod_AllowFind">C-FIND (Query)</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch custom-switch-emerald">
                                        <input class="form-check-input" type="checkbox" name="AllowStore"
                                            id="mod_AllowStore" value="1" checked>
                                        <label class="form-check-label small fw-bold text-slate-600" for="mod_AllowStore">C-STORE (Send)</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch custom-switch-emerald">
                                        <input class="form-check-input" type="checkbox" name="AllowMove" id="mod_AllowMove"
                                            value="1" checked>
                                        <label class="form-check-label small fw-bold text-slate-600" for="mod_AllowMove">C-MOVE (Retrieve)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer p-2 bg-light-soft border-0">
                        <button type="button" class="btn btn-secondary medizen-btn-minimal" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-emerald medizen-btn-minimal">SIMPAN DATA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .medizen-indicator {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 20px;
                height: 20px;
                font-size: 10px;
                font-weight: 800;
                background: #f1f5f9;
                color: #94a3b8;
                border: 1px solid #e2e8f0;
            }
            .medizen-indicator.active {
                background: #f0fdf4;
                color: #10b981;
                border-color: #10b981;
            }
            .custom-switch-emerald .form-check-input:checked {
                background-color: #10b981;
                border-color: #10b981;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Tambah Modality
                $('#btnTambahModality').click(function() {
                    $('#formModality')[0].reset();
                    $('#mod_Name').val('').attr('readonly', false).css('background', '#fff');
                    $('#modalTitle').text('TAMBAH MODALITAS BARU');
                    
                    // Set defaults
                    $('#mod_AllowEcho, #mod_AllowFind, #mod_AllowStore, #mod_AllowMove').prop('checked', true);
                    
                    $('#modalModality').modal('show');
                });

                // Edit Modality
                $('.edit-modality').click(function() {
                    const data = $(this).data('json');
                    $('#modalTitle').text('EDIT KONFIGURASI MODALITAS');
                    $('#mod_Name').val(data.Name).attr('readonly', true).css('background', '#f8fafc');
                    $('#mod_AET').val(data.AET);
                    $('#mod_Host').val(data.Host);
                    $('#mod_Port').val(data.Port);
                    $('#mod_Manufacturer').val(data.Manufacturer || '');

                    $('#mod_AllowEcho').prop('checked', data.AllowEcho !== false);
                    $('#mod_AllowFind').prop('checked', data.AllowFind !== false);
                    $('#mod_AllowStore').prop('checked', data.AllowStore !== false);
                    $('#mod_AllowMove').prop('checked', data.AllowMove !== false);

                    $('#modalModality').modal('show');
                });

                // Delete Confirmation
                $('.swal-confirm').click(function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    const title = $(this).data('swal-title') || 'Apakah Anda yakin?';
                    const text = $(this).data('swal-text') || 'Data ini akan dihapus secara permanen!';

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection