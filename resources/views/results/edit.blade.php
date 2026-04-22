@extends('layouts.app')
@section('title', 'Input Hasil Pemeriksaan')
@section('page-title', 'Hasil Radiologi')

@push('styles')
    <style>
        .mobile-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 8px 10px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            display: flex;
            gap: 6px;
            overflow-x: auto;
        }
        
        .mobile-footer::-webkit-scrollbar {
            display: none;
        }

        .mobile-footer .btn {
            white-space: nowrap;
            padding: 0.4rem 0.6rem !important;
            font-size: 0.7rem !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            padding-bottom: 70px;
        }

        .summary-card table td {
            padding: 4px 0;
            border: none;
            font-size: 0.75rem;
        }

        textarea.expertise-input {
            border-radius: 8px;
            resize: vertical;
            min-height: 50vh;
            font-size: 0.85rem;
            line-height: 1.5;
            border: 1px solid #e2e8f0;
            background-color: #fcfcfc;
            color: #1e293b;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease-in-out;
            font-family: 'Inter', sans-serif;
        }

        textarea.expertise-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.15);
            background-color: #ffffff;
            outline: none;
        }

        @media (min-width: 992px) {
            .mobile-footer {
                position: static;
                box-shadow: none;
                padding: 1rem 1.5rem;
                background: #f8fafc;
                justify-content: flex-end;
                margin-top: 0;
                border-top: 1px solid #e2e8f0;
                border-radius: 0 0 12px 12px;
            }

            .main-content {
                padding-bottom: 20px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <!-- Kolom Info Pasien (Kiri di Desktop, Atas di Mobile) -->
        <div class="col-lg-4 mb-3">
            <!-- Patient Info Accordion -->
            <div class="card card-medizen" style="position: sticky; top: 80px;">
                <div class="card-header bg-light-soft border-bottom pt-2 pb-2 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="bg-emerald-soft p-1 rounded me-2">
                            <i data-feather="user" style="width:14px;height:14px" class="text-emerald"></i>
                        </div>
                        <span class="text-slate-800 fw-bold" style="font-size: 0.85rem;">Patient Information</span>
                    </div>
                </div>
                
                <div id="collapsePatientInfo">
                    <div class="card-body summary-card pb-3 pt-2">
                        <div class="d-flex align-items-center mb-3 p-2 bg-light-soft rounded border">
                            <div class="rounded-circle bg-emerald text-white d-flex justify-content-center align-items-center me-2 shadow-sm"
                                style="width:36px;height:36px;font-weight:bold;font-size:1rem;">
                                {{ strtoupper(substr($order->patient->nama ?? 'P', 0, 1)) }}
                            </div>
                            <div>
                                <strong class="d-block text-slate-800" style="font-size: 0.85rem;">{{ $order->patient->nama ?? '' }}</strong>
                                <span class="badge bg-dark-soft text-slate-700 mt-1" style="font-size: 0.6rem;">RM: {{ $order->patient->no_rm ?? '' }}</span>
                            </div>
                        </div>

                        <table class="table mb-0">
                            <tr>
                                <td class="text-muted w-50" style="font-size: 0.7rem;">Exam Date</td>
                                <td class="text-end fw-bold text-slate-800" style="font-size: 0.7rem;">
                                    {{ \Carbon\Carbon::parse($order->scheduled_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-50" style="font-size: 0.7rem;">Gender/Age</td>
                                <td class="text-end fw-bold text-slate-800" style="font-size: 0.7rem;">{{ $order->patient->jenis_kelamin_label }} /
                                    {{ $order->patient->umur }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-50" style="font-size: 0.7rem;">Modality</td>
                                <td class="text-end fw-bold" style="font-size: 0.7rem;">
                                    <span class="badge bg-emerald-soft text-emerald px-1 py-1 border" style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">{{ $order->modality }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted w-50" style="font-size: 0.7rem;">Procedure</td>
                                <td class="text-end fw-bold text-slate-800" style="line-height:1.2; font-size: 0.7rem;">{{ $order->examinationType->name ?? '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted w-50" style="font-size: 0.7rem;">Physician</td>
                                <td class="text-end fw-bold text-slate-800" style="line-height:1.2; font-size: 0.7rem;">{{ $order->referringDoctor->name ?? '-' }}
                                </td>
                            </tr>
                            @if($order->clinical_info)
                                <tr>
                                    <td colspan="2" class="pt-2">
                                        <span class="text-muted d-block mb-1 fw-bold" style="font-size: 0.65rem;">Clinical Diagnosis:</span>
                                        <div class="p-2 border rounded bg-light-soft text-slate-700" style="font-size:0.7rem; line-height: 1.3;">
                                            {{ $order->clinical_info }}</div>
                                    </td>
                                </tr>
                            @endif
                        </table>

                        @if($order->result?->status === 'FINAL')
                            <hr class="my-2">
                            <div class="d-grid gap-2">
                                <a href="{{ route('orders.print', $order) }}" target="_blank" class="btn btn-dark btn-sm fw-bold border py-1" style="font-size: 0.7rem;">
                                    <i data-feather="printer" style="width:12px;height:12px" class="me-1"></i> PRINT
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- PACS DICOM Images --}}
            @if(isset($PACSStudy) && $PACSStudy)
                <div class="card card-medizen mt-3">
                    <div class="card-header bg-light-soft border-bottom pt-2 pb-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary-soft p-1 rounded me-2">
                                <i data-feather="image" style="width:14px;height:14px" class="text-primary"></i>
                            </div>
                            <span class="text-slate-800 fw-bold" style="font-size: 0.85rem;">DICOM Images</span>
                        </div>
                        <div class="d-flex align-items-center">
                            @php
                                $tags = $PACSStudy['MainDicomTags'] ?? [];
                            @endphp
                            <button type="button"
                                onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $tags['StudyInstanceUID'] ?? '' }}')"
                                class="btn btn-sm btn-emerald px-2 py-1 fw-bold" title="Launch OHIF Viewer" style="font-size: 0.6rem;">
                                <i data-feather="eye" style="width:12px;height:12px" class="me-1"></i> VIEWER
                            </button>
                        </div>
                    </div>
                    
                    <div id="collapseDicom">
                        <div class="card-body pb-3 pt-2">
                            <div class="row g-2">
                                @forelse($PACSStudy['SeriesData'] ?? [] as $sr)
                                    @php
                                        $srTags = $sr['MainDicomTags'] ?? [];
                                        $firstInst = $sr['_firstInstance'] ?? null;
                                    @endphp
                                    <div class="col-4 col-md-6 col-lg-6">
                                        <div class="card shadow-sm mb-0">
                                            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center p-1" style="height: 70px;">
                                                @if($firstInst)
                                                    <a href="javascript:void(0)" onclick="openImage('{{ route('pacs.instance-preview', $firstInst) }}')">
                                                        <img src="{{ route('pacs.instance-preview', $firstInst) }}" class="img-fluid rounded" style="max-height: 60px; object-fit: contain;" loading="lazy" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'90\'%3E%3Crect width=\'120\' height=\'90\' fill=\'%231a1f2e\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23666\' text-anchor=\'middle\' dy=\'.3em\' font-size=\'12\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                                                    </a>
                                                @else
                                                    <i data-feather="image" style="color:#666;width:20px;height:20px;"></i>
                                                @endif
                                            </div>
                                            <div class="card-body p-1 text-center" style="background:#f8f9fa;">
                                                <small class="d-block text-truncate mb-0" title="{{ $srTags['SeriesDescription'] ?? 'Series' }}" style="font-size: 0.6rem;">
                                                    <strong>{{ $srTags['SeriesDescription'] ?? 'Series' }}</strong>
                                                </small>
                                                <span class="badge bg-primary-soft text-primary border" style="font-size: 0.55rem;">{{ $sr['_instanceCount'] ?? 0 }} IMG</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted p-2"><small>Belum ada seri gambar.</small></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Kolom Input (Kanan di Desktop, Bawah di Mobile) -->
        <div class="col-lg-8">
            <form method="POST" action="{{ route('results.update', $order) }}" id="expertiseForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" id="formStatus" value="{{ $order->result?->status ?? 'DRAFT' }}">

                <div class="card card-medizen mb-0" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                    <div class="card-header bg-light-soft border-bottom pt-2 pb-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-emerald-soft p-1 rounded me-2">
                                <i data-feather="edit-3" style="width:14px;height:14px" class="text-emerald"></i>
                            </div>
                            <span class="fw-bold text-slate-800" style="font-size: 0.85rem;">Evaluation Expertise</span>
                        </div>
                        @if($order->result?->status === 'FINAL')
                            <span class="badge bg-success-soft text-success shadow-sm px-2 py-0 border" style="font-size: 0.6rem;"><i data-feather="check"
                                    style="width:10px;height:10px" class="me-1"></i> FINALIZED</span>
                        @elseif($order->result?->status === 'DRAFT')
                            <span class="badge bg-warning-soft text-warning shadow-sm px-2 py-0 border" style="font-size: 0.6rem;"><i data-feather="edit-2"
                                    style="width:10px;height:10px" class="me-1"></i> DRAFT</span>
                        @endif
                    </div>

                    <div class="card-body pt-2 pb-3 px-2 px-md-3">
                        <div class="mb-3 p-2 bg-light-soft rounded border shadow-sm">
                            <label class="form-label text-slate-700 small fw-bold mb-1" style="font-size: 0.75rem;"><i data-feather="copy" style="width:12px;height:12px" class="me-1 text-primary"></i> Master Template</label>
                            <select id="templateSearch" class="form-control form-control-sm" style="font-size: 0.8rem;"></select>
                        </div>
                        
                        <div class="mb-1">
                            <textarea name="expertise" id="expertise" class="form-control expertise-input p-3 shadow-sm"
                                placeholder="Type the expertise (reading) report here...">{{ old('expertise', $order->result?->expertise ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mobile-footer card-medizen d-flex gap-2 justify-content-end p-2 align-items-center" style="border-top-left-radius: 0; border-top-right-radius: 0; margin-top: -1px; border-top: 1px solid #e2e8f0; position: relative; z-index: 5;">
                    <a href="{{ route('results.index') }}" class="btn btn-light btn-sm border fw-bold px-3 py-2" title="Cancel">
                        <i data-feather="x" style="width:14px;height:14px" class="me-1"></i>
                        <span>CANCEL</span>
                    </a>
                    @if($order->patient_portal_token)
                        <button type="button" class="btn btn-info btn-sm text-white fw-bold px-3 py-2 shadow-sm"
                            onclick="copyPortalLink('{{ route('portal.result', $order->patient_portal_token) }}')" title="Copy Link">
                            <i data-feather="link" style="width:14px;height:14px" class="me-1"></i>
                            <span>COPY LINK</span>
                        </button>
                    @endif
                    @if($order->result?->status !== 'FINAL')
                        <button type="button" class="btn btn-dark btn-sm fw-bold px-3 py-2 shadow-sm"
                            onclick="saveAs('DRAFT')" title="Save Draft">
                            <i data-feather="save" style="width:14px;height:14px" class="me-1"></i>
                            <span>SAVE DRAFT</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-emerald btn-sm fw-bold px-3 py-2 shadow-sm"
                        onclick="saveAs('FINAL')" title="Final Approval">
                        <i data-feather="check-circle" style="width:14px;height:14px" class="me-1"></i>
                        <span>FINAL APPROVAL</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Viewer Modals --}}
    {{-- Image Modal (for quick thumbnail preview) --}}
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" style="background:rgba(0,0,0,0.95)">
            <div class="modal-content" style="background:transparent;border:none">
                <div class="modal-header border-0" style="position:absolute;top:0;right:0;z-index:10">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" data-bs-dismiss="modal"
                    style="cursor:zoom-out">
                    <img id="modalImage" src="" style="max-width:100%;max-height:100vh;object-fit:contain"
                        alt="DICOM Image">
                </div>
            </div>
        </div>
    </div>

    {{-- Viewer Modal (for full OHIF) --}}
    <div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary py-2">
                    <h5 class="modal-title text-white"><i data-feather="monitor"
                            style="width:18px;height:18px;margin-right:8px"></i> DICOM Viewer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="viewerIframe" src=""
                        style="width:100%; height:100%; border:none; background:#000;"></iframe>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function saveAs(status) {
                if (status === 'FINAL') {
                    Swal.fire({
                        title: 'Finalisasi Hasil?',
                        text: "Setelah final, hasil bisa dicetak dan akan mengubah status antrean laporan ini. Anda yakin?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Finalisasi',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('formStatus').value = status;
                            document.getElementById('expertiseForm').submit();
                        }
                    });
                } else {
                    document.getElementById('formStatus').value = status;
                    document.getElementById('expertiseForm').submit();
                }
            }

            function openImage(url) {
                document.getElementById('modalImage').src = url;
                new bootstrap.Modal(document.getElementById('imageModal')).show();
            }

            document.getElementById('imageModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('modalImage').src = '';
            });

            function openViewer(url) {
                document.getElementById('viewerIframe').src = url;
                new bootstrap.Modal(document.getElementById('viewerModal')).show();
            }

            document.getElementById('viewerModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('viewerIframe').src = '';
            });

            function copyPortalLink(url) {
                navigator.clipboard.writeText(url).then(function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Link berhasil disalin',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }, function(err) {
                    window.prompt("Terjadi kendala. Salin link ini manual:", url);
                });
            }

            $(document).ready(function() {
                var $tSearch = $('#templateSearch');
                if($tSearch.length) {
                    $tSearch.select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Ketik Nomor/Nama Template...',
                        allowClear: true,
                        // minimumInputLength is removed to allow immediate dropdown
                        ajax: {
                            url: '{{ route('api.templates.search') }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    term: params.term || '' // Pass empty string if no term
                                };
                            }
                        }
                    });

                    $tSearch.on('select2:select', function (e) {
                        var tplId = e.params.data.id;
                        if(!tplId) return;

                        var $textarea = $('#expertise');
                        $textarea.prop('disabled', true); // Temporarily disable while loading

                        $.ajax({
                            url: '{{ url("api/templates") }}/' + tplId + '/expertise',
                            method: 'GET',
                            success: function(res) {
                                if(res.expertise) {
                                    var currentVal = $textarea.val().trim();
                                    var newVal = res.expertise;
                                    if(currentVal.length > 0) {
                                        Swal.fire({
                                            title: 'Timpa Laporan?',
                                            text: "Ruang laporan saat ini sudah ada isinya. Bagaimana menaruh template ini?",
                                            icon: 'question',
                                            showCancelButton: true,
                                            showCloseButton: true,
                                            confirmButtonColor: '#0d6efd',
                                            cancelButtonColor: '#198754',
                                            confirmButtonText: '<i data-feather="file-text"></i> Timpa Semua',
                                            cancelButtonText: '<i data-feather="plus"></i> Sambung di Bawah',
                                            didOpen: () => feather.replace()
                                        }).then((alertResult) => {
                                            if (alertResult.isConfirmed) {
                                                $textarea.val(newVal);
                                            } else if (alertResult.dismiss === Swal.DismissReason.cancel) {
                                                $textarea.val(currentVal + '\n\n' + newVal);
                                            }
                                        });
                                    } else {
                                        $textarea.val(newVal);
                                    }
                                }
                                $textarea.prop('disabled', false);
                                // Optional: Clear the selection after apply
                                $tSearch.val(null).trigger('change');
                            },
                            error: function() {
                                Swal.fire('Error', 'Gagal memuat isi template.', 'error');
                                $textarea.prop('disabled', false);
                            }
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
