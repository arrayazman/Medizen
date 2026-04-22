@extends('layouts.app')
@section('title', 'Detail Order')
@section('page-title', 'Detail Order Radiologi')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 fw-bold mb-0 text-dark">REF: #{{ $order->order_number }}</h1>
            <p class="text-muted mb-0" style="font-size: 0.65rem; letter-spacing: 0.5px;">RADIOLOGY CLINICAL REQUEST DETAIL
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($order->status !== 'CANCELLED')
                <a href="{{ route('orders.print-receipt', $order) }}" target="_blank" class="btn btn-light border btn-sm px-3"
                    style="border-radius: 2px; font-size: 0.7rem;">
                    <i data-feather="printer" style="width:12px;height:12px" class="me-1"></i> RECEIPT
                </a>
            @endif

            @if(in_array($order->status, ['ORDERED', 'SENT_TO_PACS', 'WAITING_SAMPLE', 'SAMPLE_TAKEN']))
                <a href="{{ route('orders.edit', $order) }}" class="btn btn-light border btn-sm px-3"
                    style="border-radius: 2px; font-size: 0.7rem;">EDIT</a>
            @endif

            @if($order->status !== 'CANCELLED')
                <form action="{{ route('orders.send-worklist', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light border btn-sm px-3 swal-confirm"
                        style="border-radius: 2px; font-size: 0.7rem;" data-swal-title="PACS Synchronization"
                        data-swal-text="Kirim/Resend data request ke server PACS?" data-swal-confirm-text="Ya, Sinkronkan">
                        <i data-feather="send" style="width:12px;height:12px" class="me-1"></i> KIRIM WORKLIST
                    </button>
                </form>
            @endif

            @if(in_array($order->status, ['ORDERED', 'SENT_TO_PACS', 'WAITING_SAMPLE']))
                <form action="{{ route('orders.take-sample', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-emerald btn-sm px-3 shadow-sm swal-confirm"
                        style="border-radius: 2px; font-size: 0.7rem;" data-swal-title="Acquire Sample"
                        data-swal-text="Tandai bahwa sample klinis telah berhasil diambil?"
                        data-swal-confirm-text="Ya, Tandai Diambil">
                        <i data-feather="check-circle" style="width:12px;height:12px" class="me-1"></i> TAKE SAMPLE
                    </button>
                </form>
            @endif

            @if(in_array($order->status, ['ORDERED', 'SENT_TO_PACS', 'WAITING_SAMPLE', 'SAMPLE_TAKEN']))
                <form action="{{ route('orders.start-examination', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-emerald btn-sm px-3 shadow-sm swal-confirm"
                        style="border-radius: 2px; font-size: 0.7rem;" data-swal-title="Begin Examination"
                        data-swal-text="Mulai prosedur pemeriksaan radiologi untuk pasien ini?"
                        data-swal-confirm-text="Ya, Mulai">
                        <i data-feather="activity" style="width:12px;height:12px" class="me-1"></i> BEGIN EXAM
                    </button>
                </form>
            @endif

            @if($order->status == 'IN_PROGRESS')
                <form action="{{ route('orders.complete', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-emerald btn-sm px-3 shadow-sm swal-confirm"
                        style="border-radius: 2px; font-size: 0.7rem;" data-swal-title="Complete Examination"
                        data-swal-text="Tandai bahwa pemeriksaan telah selesai dilakukan (Status: COMPLETED)?"
                        data-swal-confirm-text="Ya, Selesai">
                        <i data-feather="check" style="width:12px;height:12px" class="me-1"></i> COMPLETE
                    </button>
                </form>
                <a href="{{ route('results.edit', $order) }}" class="btn btn-emerald btn-sm px-3 shadow-sm"
                    style="border-radius: 2px; font-size: 0.7rem;"><i
                        data-feather="file-text" style="width:12px;height:12px" class="me-1"></i> UPDATE RESULTS</a>
            @endif

            @if($order->studyMetadata && $order->studyMetadata->PACS_id && auth()->user()->hasRole(['super_admin', 'dokter_radiologi']))
                <a href="{{ route('viewer.show', $order) }}" class="btn btn-emerald btn-sm px-3 shadow-sm"
                    style="border-radius: 2px; font-size: 0.7rem;" target="_blank"><i data-feather="eye"
                        style="width:12px;height:12px" class="me-1"></i> VIEW DICOM</a>
            @endif

            @if(!in_array($order->status, ['COMPLETED', 'REPORTED', 'CANCELLED']))
                <form action="{{ route('orders.cancel', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light border text-danger btn-sm px-3 swal-confirm"
                        style="border-radius: 2px; font-size: 0.7rem;" data-swal-title="Abort Order"
                        data-swal-text="Yakin ingin membatalkan/abort pemeriksaan ini secara permanen?" data-swal-type="error"
                        data-swal-confirm-text="Ya, Batalkan">ABORT</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">ORDER INFORMATION</h6>
                </div>
                <div class="card-body px-3 py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted py-1" width="40%" style="font-size: 0.7rem;">Accession ID</td>
                                    <td class="py-1"><span class="fw-bold text-emerald"
                                            style="font-size: 0.75rem;">{{ $order->accession_number }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Clinical Status</td>
                                    <td class="py-1">{!! str_replace('badge', 'badge px-2 py-1', $order->status_badge) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Priority Level</td>
                                    <td class="py-1"><span
                                            class="badge {{ $order->priority == 'ROUTINE' ? 'bg-light text-dark border' : 'bg-danger-soft text-danger' }} px-2 py-1"
                                            style="font-size: 0.65rem;">{{ $order->priority }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Instance UID</td>
                                    <td class="py-1">
                                        <small class="text-muted d-block text-truncate"
                                            style="font-size: 0.6rem; max-width: 150px;"
                                            title="{{ $order->study_instance_uid }}">{{ $order->study_instance_uid }}</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted py-1" width="40%" style="font-size: 0.7rem;">Target Modality</td>
                                    <td class="py-1"><span class="badge bg-emerald-soft text-emerald px-2 py-1"
                                            style="font-size: 0.65rem;">{{ $order->modality }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Exam Type</td>
                                    <td class="py-1 fw-bold text-dark" style="font-size: 0.75rem;">
                                        {{ $order->examinationType->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Schedule</td>
                                    <td class="py-1 text-dark" style="font-size: 0.75rem;">{{ $order->formatted_date }}
                                        <span class="text-muted mx-1">|</span>
                                        {{ \Carbon\Carbon::parse($order->scheduled_time)->format('H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Assigned Unit</td>
                                    <td class="py-1 text-dark" style="font-size: 0.75rem;">{{ $order->room->name ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1" style="font-size: 0.7rem;">Service Fee</td>
                                    <td class="py-1"><span class="fw-bold text-dark" style="font-size: 0.75rem;">Rp {{ number_format($order->examinationType->price ?? 0, 0, ',', '.') }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                @if($order->procedure_description || $order->clinical_info || $order->notes)
                    <div class="px-3 pb-3">
                        <div class="bg-light-soft rounded-1 p-2">
                            @if($order->procedure_description)
                                <div class="mb-1">
                                    <label class="text-muted fw-bold d-block mb-0" style="font-size: 0.6rem;">PROCEDURE
                                        DESCRIPTION</label>
                                    <span class="text-dark" style="font-size: 0.75rem;">{{ $order->procedure_description }}</span>
                                </div>
                            @endif
                            @if($order->clinical_info)
                                <div class="mb-1">
                                    <label class="text-muted fw-bold d-block mb-0" style="font-size: 0.6rem;">CLINICAL
                                        INDICATION</label>
                                    <span class="text-dark" style="font-size: 0.75rem;">{{ $order->clinical_info }}</span>
                                </div>
                            @endif
                            @if($order->notes)
                                <div>
                                    <label class="text-muted fw-bold d-block mb-0" style="font-size: 0.6rem;">SPECIAL NOTES</label>
                                    <span class="text-dark" style="font-size: 0.75rem;">{{ $order->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="card-footer bg-white border-top border-light p-0">
                    <div class="row g-0 text-center">
                        <div class="col-4 border-end py-2">
                            <div class="text-muted fw-bold" style="font-size: 0.55rem;">SAMPLING</div>
                            <strong class="text-dark"
                                style="font-size: 0.7rem;">{{ $order->waktu_sample ? \Carbon\Carbon::parse($order->waktu_sample)->format('d/m/Y H:i') : 'PENDING' }}</strong>
                        </div>
                        <div class="col-4 border-end py-2">
                            <div class="text-muted fw-bold" style="font-size: 0.55rem;">EXECUTION</div>
                            <strong class="text-dark"
                                style="font-size: 0.7rem;">{{ $order->waktu_mulai_periksa ? \Carbon\Carbon::parse($order->waktu_mulai_periksa)->format('d/m/Y H:i') : 'PENDING' }}</strong>
                        </div>
                        <div class="col-4 py-2">
                            <div class="text-muted fw-bold" style="font-size: 0.55rem;">REPORTING</div>
                            <strong class="text-dark"
                                style="font-size: 0.7rem;">{{ $order->result?->waktu_hasil ? \Carbon\Carbon::parse($order->result->waktu_hasil)->format('d/m/Y H:i') : 'PENDING' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->result?->expertise || $order->status === 'COMPLETED' || $order->status === 'REPORTED' || $order->status === 'VALIDATED')
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                    <div
                        class="card-header bg-white border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">EXPERT ANALYSIS / RESULTS</h6>
                        <div class="d-flex gap-1">
                            @if(in_array($order->status, ['IN_PROGRESS', 'COMPLETED', 'REPORTED', 'VALIDATED']))
                                <a href="{{ route('results.edit', $order) }}" class="btn btn-light btn-sm border px-2"
                                    style="border-radius: 2px; font-size: 0.65rem;">
                                    <i data-feather="edit-2" style="width:10px;height:10px" class="me-1"></i>
                                    {{ $order->result?->expertise ? 'REVISE' : 'INPUT' }}
                                </a>
                            @endif
                            @if($order->result?->expertise)
                                <a href="{{ route('orders.print', $order) }}" target="_blank"
                                    class="btn btn-emerald btn-sm px-2 shadow-sm" style="border-radius: 2px; font-size: 0.65rem;">
                                    <i data-feather="printer" style="width:10px;height:10px" class="me-1"></i> PRINT
                                </a>
                                @if($order->patient_portal_token)
                                    <button type="button" class="btn btn-light btn-sm border px-2"
                                        style="border-radius: 2px; font-size: 0.65rem;"
                                        onclick="copyPortalLink('{{ route('portal.result', $order->patient_portal_token) }}')">
                                        <i data-feather="share-2" style="width:10px;height:10px" class="me-1"></i> PORTAL
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="card-body px-3 py-2">
                        @if($order->result?->expertise)
                            <div class="p-3 bg-light-soft rounded border border-light text-dark"
                                style="font-size: 0.85rem; line-height: 1.6; min-height: 100px;">
                                {!! nl2br(e($order->result->expertise)) !!}
                            </div>
                        @else
                            <div class="text-muted text-center py-4 bg-light-soft rounded border border-dashed"
                                style="font-size: 0.75rem;">
                                EXPERT ANALYSIS HAS NOT BEEN SUBMITTED YET.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">

            {{-- Patient Info --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">SUBJECT DATA</h6>
                </div>
                <div class="card-body px-3 py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted py-1" width="40%" style="font-size: 0.7rem;">Medical Record</td>
                            <td class="py-1 fw-bold text-dark" style="font-size: 0.8rem;">
                                {{ $order->patient->no_rm ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1" style="font-size: 0.7rem;">Full Name</td>
                            <td class="py-1 fw-bold text-emerald" style="font-size: 0.85rem;">
                                {{ $order->patient->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1" style="font-size: 0.7rem;">Identity / Bio</td>
                            <td class="py-1 text-dark" style="font-size: 0.75rem;">
                                {{ $order->patient->jenis_kelamin_label ?? '-' }} <span class="text-muted mx-1">/</span>
                                {{ $order->patient->umur ?? '-' }} Yrs
                            </td>
                        </tr>
                    </table>
                    <div class="mt-2 text-center border-top pt-2">
                        <a href="{{ route('patients.show', $order->patient) }}"
                            class="text-emerald text-decoration-none fw-bold" style="font-size: 0.65rem;">VIEW FULL MEDICAL
                            RECORD <i data-feather="arrow-right" style="width:12px"></i></a>
                    </div>
                </div>
            </div>

            {{-- Study Metadata --}}
            @if($order->studyMetadata)
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                    <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">PACS METRICS</h6>
                    </div>
                    <div class="card-body px-3 py-2">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted py-1" width="40%" style="font-size: 0.7rem;">Capture Series</td>
                                <td class="py-1 text-dark fw-bold" style="font-size: 0.75rem;">
                                    {{ $order->studyMetadata->series_count }} <span class="text-muted fw-normal">SETS</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted py-1" style="font-size: 0.7rem;">Total Instances</td>
                                <td class="py-1 text-dark fw-bold" style="font-size: 0.75rem;">
                                    {{ $order->studyMetadata->instance_count }} <span class="text-muted fw-normal">IMGS</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted py-1" style="font-size: 0.7rem;">Acquisition Date</td>
                                <td class="py-1 text-dark fw-bold" style="font-size: 0.75rem;">
                                    {{ $order->studyMetadata->study_date ? $order->studyMetadata->study_date->format('d/m/Y') : 'MANUAL' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Personnel --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">AUTHORIZED PERSONNEL</h6>
                </div>
                <div class="card-body px-3 py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted py-1" width="40%" style="font-size: 0.7rem;">Referring Medic</td>
                            <td class="py-1 text-dark" style="font-size: 0.75rem;">
                                {{ $order->referringDoctor->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1" style="font-size: 0.7rem;">Head Radiographer</td>
                            <td class="py-1 text-dark" style="font-size: 0.75rem;">{{ $order->radiographer->name ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted py-1" style="font-size: 0.7rem;">Registered By</td>
                            <td class="py-1 text-dark" style="font-size: 0.75rem;">{{ $order->creator->name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- PACS DICOM Images --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                <div
                    class="card-header bg-white border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">IMAGING ARCHIVE</h6>
                    <div class="d-flex gap-1">
                        @if($PACSStudy)
                            @php
                                $tags = $PACSStudy['MainDicomTags'] ?? [];
                            @endphp
                            <button type="button"
                                onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $tags['StudyInstanceUID'] ?? '' }}')"
                                class="btn btn-light btn-sm border-0 px-2" style="font-size: 0.65rem;"
                                title="Buka Viewer (OHIF)">
                                <i data-feather="monitor" style="width:12px;height:12px" class="text-emerald me-1"></i> FULL
                                VIEWER
                            </button>
                        @endif
                        <a href="{{ route('pacs.upload', ['accession' => $order->accession_number]) }}"
                            class="btn btn-light btn-sm border-0 px-2" style="font-size: 0.65rem;"
                            title="Upload Manual/Eksternal ke PACS">
                            <i data-feather="upload-cloud" style="width:12px;height:12px" class="text-primary me-1"></i>
                            IMPORT
                        </a>
                    </div>
                </div>
                @if($PACSStudy)
                    <div class="card-body p-2 pt-1">
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            @forelse($PACSStudy['SeriesData'] ?? [] as $sr)
                                @php
                                    $srTags = $sr['MainDicomTags'] ?? [];
                                    $firstInst = $sr['_firstInstance'] ?? null;
                                @endphp
                                <div class="card border-0 bg-light-soft mb-0" style="width: 130px; border-radius: 4px;">
                                    <div class="bg-dark d-flex align-items-center justify-content-center p-1"
                                        style="height: 90px; border-top-left-radius: 4px; border-top-right-radius: 4px;">
                                        @if($firstInst)
                                            <a href="javascript:void(0)"
                                                onclick="openImage('{{ route('pacs.instance-preview', $firstInst) }}')">
                                                <img src="{{ route('pacs.instance-preview', $firstInst) }}" class="img-fluid rounded"
                                                    style="max-height: 80px; object-fit: contain;" loading="lazy"
                                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'90\'%3E%3Crect width=\'120\' height=\'90\' fill=\'%231a1f2e\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23666\' text-anchor=\'middle\' dy=\'.3em\' font-size=\'12\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                                            </a>
                                        @else
                                            <i data-feather="image" style="color:#666;width:20px;height:20px;"></i>
                                        @endif
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <div class="text-truncate fw-bold text-dark mb-1"
                                            title="{{ $srTags['SeriesDescription'] ?? 'Series' }}" style="font-size: 0.65rem;">
                                            {{ $srTags['SeriesDescription'] ?? 'NO DESC' }}
                                        </div>
                                        <div class="d-flex justify-content-center gap-1">
                                            <span class="badge bg-white text-dark border px-1"
                                                style="font-size: 0.55rem;">{{ $srTags['Modality'] ?? '-' }}</span>
                                            <span class="badge bg-emerald-soft text-emerald px-1"
                                                style="font-size: 0.55rem;">{{ $sr['_instanceCount'] ?? 0 }} I</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted p-3 bg-light-soft rounded w-100" style="font-size: 0.7rem;">NO
                                    SERIES DATA CAPTURED.</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="card-body text-center p-4">
                        <i data-feather="image" class="text-muted opacity-30 mb-2" style="width:32px;height:32px"></i>
                        <p class="text-muted mb-0 small" style="font-size: 0.7rem;">NO DICOM IMAGES DETECTED IN THE ARCHIVE FOR
                            THIS ACCESSION.</p>
                        <a href="{{ route('pacs.upload', ['accession' => $order->accession_number]) }}"
                            class="btn btn-light border btn-sm mt-3 px-3 fw-bold"
                            style="font-size: 0.65rem; border-radius: 2px;">
                            <i data-feather="upload-cloud" style="width:12px;height:12px" class="me-1"></i> ATTACH IMAGES
                        </a>
                    </div>
                @endif
            </div>
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

    {{-- Modal Input Hasil/Expertise --}}
    <div class="modal fade" id="expertiseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg border-0">
            <div class="modal-content border-0 shadow" style="border-radius: 4px;">
                <form action="{{ route('orders.input-expertise', $order) }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom-0 pt-3 px-3">
                        <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">SUBMIT RADIOLOGY EXPERTISE</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-3 py-2">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">PROFESSIONAL
                                ANALYSIS</label>
                            <textarea name="expertise" class="form-control border-0 bg-light-soft" rows="12"
                                style="font-size: 0.85rem; line-height: 1.6;"
                                placeholder="Enter clinical findings and analysis here..."
                                required>{{ old('expertise', $order->result?->expertise) }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-3 pb-3">
                        <button type="button" class="btn btn-light btn-sm px-3" style="border-radius: 2px;"
                            data-bs-dismiss="modal">DISCARD</button>
                        <button type="submit" class="btn btn-emerald btn-sm px-4 shadow-sm"
                            style="border-radius: 2px;">COMMIT REPORT</button>
                    </div>
                </form>
            </div>
        </div>


@endsection

@push('scripts')
    <script>

        // For quick image preview
        function openImage(url) {
            document.getElementById('modalImage').src = url;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        document.getElementById('imageModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalImage').src = '';
        });

        // For full OHIF viewer
        function openViewer(url) {
            // Set the iframe source
            document.getElementById('viewerIframe').src = url;

            // Show the modal
            var viewerModal = new bootstrap.Modal(document.getElementById('viewerModal'));
            viewerModal.show();
        }

        // Clear iframe when modal is closed to stop processing/audio
        document.getElementById('viewerModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('viewerIframe').src = '';
        });

        function copyPortalLink(url) {
            navigator.clipboard.writeText(url).then(function () {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Link berhasil disalin',
                    showConfirmButton: false,
                    timer: 2000
                });
            }, function (err) {
                // Fallback using prompt
                window.prompt("Terjadi kendala. Salin link ini manual:", url);
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        .bg-emerald-soft {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
        }

        .bg-danger-soft {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
        }

        .bg-light-soft {
            background-color: #f8fafc;
        }

        .btn-emerald {
            background-color: #10b981;
            color: #fff;
            border: none;
            transition: 0.2s;
        }

        .btn-emerald:hover {
            background-color: #059669;
            color: #fff;
            transform: translateY(-1px);
        }

        .text-emerald {
            color: #10b981 !important;
        }

        .border-dashed {
            border-style: dashed !important;
        }
    </style>
@endpush