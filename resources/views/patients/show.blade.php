@extends('layouts.app')
@section('title', 'Profil Pasien')
@section('page-title', 'Detail Rekam Medis')

@section('content')
    <div class="row g-3">
        <!-- Patient Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;">Patient Profile</h6>
                        <span class="badge bg-emerald-soft text-emerald "
                            style="font-size: 0.55rem;">ACTIVE</span>
                    </div>
                </div>
                <div class="card-body px-3 pb-3 pt-2">
                    <div class="text-center mb-3 pt-1">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-2"
                            style="width: 60px; height: 60px; border: 2px solid #f1f5f9;">
                            <i data-feather="user" class="text-muted" style="width: 30px; height: 30px;"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 1rem;">{{ $patient->nama }}</h6>
                        <span class="badge bg-light text-dark border "
                            style="font-size: 0.65rem; letter-spacing: 0.5px;">RM: {{ $patient->no_rm }}</span>
                    </div>

                    <div class="border-top pt-2 mt-1">
                        <div class="row mb-2">
                            <div class="col-5 text-uppercase  text-muted small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Identity ID</div>
                            <div class="col-7 text-dark small" style="font-size: 0.75rem;">
                                {{ $patient->nik ?? 'UNREGISTERED' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-uppercase  text-muted small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Gender</div>
                            <div class="col-7">
                                @if($patient->jenis_kelamin == 'L')
                                    <span class="text-primary fw-bold" style="font-size: 0.7rem;">LAKI-LAKI</span>
                                @else
                                    <span class="text-danger fw-bold" style="font-size: 0.7rem;">PEREMPUAN</span>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-uppercase  text-muted small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Birth Info</div>
                            <div class="col-7 text-dark small" style="font-size: 0.75rem;">
                                {{ $patient->tgl_lahir ? $patient->tgl_lahir->format('d/m/Y') : '-' }}
                                <div class="text-muted mt-0" style="font-size: 0.6rem;">Age: {{ $patient->umur }} Yrs</div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-uppercase  text-muted small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Contact</div>
                            <div class="col-7 text-dark small" style="font-size: 0.75rem;"><i data-feather="phone"
                                    class="me-1" style="width: 10px;"></i>{{ $patient->no_hp ?? '-' }}</div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-5 text-uppercase  text-muted small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Address</div>
                            <div class="col-7 text-dark small" style="line-height: 1.4; font-size: 0.7rem;">
                                {{ $patient->alamat ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('patients.edit', $patient) }}" class="btn btn-light border btn-sm w-100 py-1"
                            style="border-radius: 2px; font-size: 0.7rem;">
                            <i data-feather="edit-2" class="me-1" style="width: 10px; height: 10px;"></i> UPDATE RECORD
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content: Tabbed History & PACS -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-2 px-0">
                    <ul class="nav nav-tabs border-bottom-0 px-3" id="patientDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2 px-0 me-3 border-0 text-dark fw-bold position-relative"
                                id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button"
                                role="tab" aria-controls="history-pane" aria-selected="true"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                EXAMINATION HISTORY
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 px-0 border-0 text-muted fw-bold position-relative" id="pacs-tab"
                                data-bs-toggle="tab" data-bs-target="#pacs-pane" type="button" role="tab"
                                aria-controls="pacs-pane" aria-selected="false"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                DICOM ARCHIVE (PACS)
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="patientDetailTabsContent">
                    <!-- Tab Pane: History -->
                    <div class="tab-pane fade show active" id="history-pane" role="tabpanel" aria-labelledby="history-tab"
                        tabindex="0">
                        <div
                            class="px-3 py-2 border-top border-light d-flex justify-content-between align-items-center bg-light-soft">
                            <p class="text-muted  mb-0" style="font-size: 0.55rem; letter-spacing: 0.5px;">
                                RECORDS FOUND: {{ count($patient->orders) }}</p>
                            <button type="button" class="btn btn-emerald btn-sm px-2 shadow-sm"
                                style="border-radius: 2px; font-size: 0.65rem; height: 26px; display: flex; align-items: center;"
                                data-bs-toggle="modal" data-bs-target="#addOrderModal">
                                <i data-feather="plus" class="me-1" style="width: 12px; height: 12px;"></i> NEW ORDER
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="border-bottom border-light bg-light-soft">
                                        <th class="py-2 px-3 text-uppercase  text-muted"
                                            style="font-size: 0.6rem; letter-spacing: 1px;">No. Order</th>
                                        <th class="py-2 px-2 text-uppercase  text-muted"
                                            style="font-size: 0.6rem; letter-spacing: 1px;">Study Info</th>
                                        <th class="py-2 px-2 text-uppercase  text-muted text-center"
                                            style="font-size: 0.6rem; letter-spacing: 1px;">Status</th>
                                        <th class="py-2 px-3 text-uppercase  text-muted text-end"
                                            style="font-size: 0.6rem; letter-spacing: 1px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($patient->orders as $order)
                                        <tr class="border-bottom border-light">
                                            <td class="px-3 py-2">
                                                <span class="fw-bold text-dark d-block "
                                                    style="font-size: 0.75rem;">#{{ $order->order_number }}</span>
                                                <span class="text-muted "
                                                    style="font-size: 0.6rem;">{{ $order->formatted_date }}</span>
                                            </td>
                                            <td class="px-2 py-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-emerald-soft text-emerald  px-2 py-1"
                                                        style="font-size: 0.55rem;">{{ $order->modality }}</span>
                                                    <span class="text-dark fw-medium small"
                                                        style="font-size: 0.75rem;">{{ $order->examinationType->name ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <div style="transform: scale(0.85);">
                                                    {!! str_replace('badge', 'badge  px-2 py-1', $order->status_badge) !!}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-end">
                                                <a href="{{ route('orders.show', $order) }}" class="btn btn-light btn-sm border"
                                                    style="padding: 2px 10px; border-radius: 2px; font-size: 0.65rem;">
                                                    DETAIL <i data-feather="chevron-right"
                                                        style="width: 10px; height: 10px; margin-left: 1px;"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 opacity-50 "
                                                style="font-size: 0.65rem;">NO ORDER HISTORY FOUND</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Pane: PACS -->
                    <div class="tab-pane fade" id="pacs-pane" role="tabpanel" aria-labelledby="pacs-tab" tabindex="0">
                        <div
                            class="px-3 py-2 border-top border-light d-flex justify-content-between align-items-center bg-light-soft">
                            <p class="text-muted  mb-0" style="font-size: 0.55rem; letter-spacing: 0.5px;">
                                PACS CONNECTED: {{ count($PACSStudies) }} STUDY FOUND</p>
                            @if(count($PACSStudies) > 0)
                                <a href="{{ route('pacs.patient-detail', reset($PACSStudies)['ParentPatient'] ?? '') }}"
                                    class="btn btn-emerald btn-sm px-2"
                                    style="border-radius: 2px; font-size: 0.65rem; height: 26px; display: flex; align-items: center;"
                                    target="_blank">
                                    <i data-feather="external-link" class="me-1" style="width: 12px; height: 12px;"></i> PACS
                                    PROFILE
                                </a>
                            @endif
                        </div>
                        <div>
                            @forelse($PACSStudies as $study)
                                @php
                                    $tags = $study['MainDicomTags'] ?? [];
                                    $studyDate = isset($tags['StudyDate']) && strlen($tags['StudyDate']) == 8 ? \Carbon\Carbon::createFromFormat('Ymd', $tags['StudyDate'])->format('d/m/Y') : '-';
                                @endphp
                                <div class="border-bottom px-3 py-3 study-row">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 0.85rem;">
                                                {{ $tags['StudyDescription'] ?? 'EXAMINATION STUDY' }}</h6>
                                            <div class="d-flex gap-2 mt-1">
                                                <span class="text-muted" style="font-size: 0.65rem;"><i data-feather="calendar"
                                                        class="me-1" style="width: 10px;"></i>{{ $studyDate }}</span>
                                                <span class="text-muted" style="font-size: 0.65rem;"><i data-feather="tag"
                                                        class="me-1" style="width: 10px;"></i>ACC: <code
                                                        class="text-emerald">{{ $tags['AccessionNumber'] ?? '-' }}</code></span>
                                                <span class="badge bg-emerald-soft text-emerald  px-1"
                                                    style="font-size: 0.55rem; padding-top: 2px; padding-bottom: 2px;">{{ $tags['ModalitiesInStudy'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <button type="button"
                                            onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $tags['StudyInstanceUID'] ?? '' }}')"
                                            class="btn btn-dark btn-sm px-2 shadow-sm"
                                            style="border-radius: 2px; font-size: 0.65rem; height: 28px;">
                                            <i data-feather="monitor" class="me-1" style="width: 12px; height: 12px;"></i> VIEW
                                        </button>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($study['SeriesData'] ?? [] as $sr)
                                            @php
                                                $srTags = $sr['MainDicomTags'] ?? [];
                                                $firstInst = $sr['_firstInstance'] ?? null;
                                            @endphp
                                            <div class="card border shadow-none mb-0 series-card"
                                                style="width: 120px; border-radius: 4px; transition: all 0.2s;">
                                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center p-1"
                                                    style="height: 85px; border-top-left-radius: 4px; border-top-right-radius: 4px;">
                                                    @if($firstInst)
                                                        <a href="javascript:void(0)"
                                                            onclick="openImage('{{ route('pacs.instance-preview', $firstInst) }}')">
                                                            <img src="{{ route('pacs.instance-preview', $firstInst) }}"
                                                                class="img-fluid" style="max-height: 80px; object-fit: contain;"
                                                                loading="lazy"
                                                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'90\'%3E%3Crect width=\'120\' height=\'90\' fill=\'%231a1f2e\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23666\' text-anchor=\'middle\' dy=\'.3em\' font-size=\'12\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                                                        </a>
                                                    @else
                                                        <i data-feather="image" style="color:#666;width:20px;height:20px;"></i>
                                                    @endif
                                                </div>
                                                <div class="card-body p-1 bg-white"
                                                    style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
                                                    <div class="text-truncate fw-bold text-dark mb-1" style="font-size: 0.6rem;"
                                                        title="{{ $srTags['SeriesDescription'] ?? 'Series' }}">
                                                        {{ $srTags['SeriesDescription'] ?? 'Series Description' }}
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge bg-light text-muted fw-bold "
                                                            style="font-size: 0.55rem; padding: 1px 3px;">{{ $srTags['Modality'] ?? '-' }}</span>
                                                        <span class="text-emerald fw-bold "
                                                            style="font-size: 0.55rem;">{{ $sr['_instanceCount'] ?? 0 }} I</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 opacity-40">
                                    <i data-feather="image" style="width:32px;height:32px;" class="mb-2"></i>
                                    <p class=" small mb-1" style="font-size: 0.65rem;">NO PACS RECORD DETECTED</p>
                                    <small class="text-muted" style="font-size: 0.65rem;">Target ID:
                                        {{ $patient->no_rm }}</small>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Order Modal --}}
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 4px;">
                <form action="{{ route('orders.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="modal-header border-bottom-0 bg-white pt-3 px-3 pb-0">
                        <h6 class="modal-title fw-bold text-dark" style="font-size: 0.9rem;">Initiate New Radiology Order
                        </h6>
                    </div>
                    <div class="modal-body px-3 py-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Imaging Modality <span
                                        class="text-danger">*</span></label>
                                <select name="modality" class="form-select border-0 bg-light-soft"
                                    style="font-size: 0.8rem; height: 32px;" required>
                                    <option value="">Select Modality</option>
                                    @foreach($modalities ?? [] as $mod)
                                        @php 
                                            $modAE = is_object($mod) ? $mod->ae_title : ($mod['ae_title'] ?? '');
                                            $modName = is_object($mod) ? $mod->name : ($mod['name'] ?? '');
                                        @endphp
                                        <option value="{{ $modAE }}">
                                            {{ $modAE }} - {{ $modName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Exam Classification</label>
                                <select name="examination_type_id" class="form-select border-0 bg-light-soft"
                                    style="font-size: 0.8rem; height: 32px;">
                                    <option value="">Select Examination</option>
                                    @foreach($examinationTypes ?? [] as $et)
                                        @php
                                            $etId = is_object($et) ? $et->id : ($et['id'] ?? '');
                                            $etName = is_object($et) ? $et->name : ($et['name'] ?? '');
                                            $etMod = is_object($et) ? ($et->modality->ae_title ?? '-') : ($et['modality']['ae_title'] ?? '-');
                                        @endphp
                                        <option value="{{ $etId }}">
                                            {{ $etName }} ({{ $etMod }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Requesting Personnel</label>
                                <select name="referring_doctor_id" class="form-select border-0 bg-light-soft"
                                    style="font-size: 0.8rem; height: 32px;">
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors ?? [] as $doc)
                                        @php
                                            $docId = is_object($doc) ? $doc->id : ($doc['id'] ?? '');
                                            $docName = is_object($doc) ? $doc->name : ($doc['name'] ?? '');
                                        @endphp
                                        <option value="{{ $docId }}">
                                            {{ $docName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Priority Level <span
                                        class="text-danger">*</span></label>
                                <select name="priority" class="form-select border-0 bg-light-soft"
                                    style="font-size: 0.8rem; height: 32px;" required>
                                    <option value="ROUTINE">ROUTINE</option>
                                    <option value="URGENT">URGENT</option>
                                    <option value="STAT">STAT (CITO)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Schedule Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="scheduled_date" class="form-control border-0 bg-light-soft px-2"
                                    style="font-size: 0.8rem; height: 32px;" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Schedule Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" name="scheduled_time" class="form-control border-0 bg-light-soft px-2"
                                    style="font-size: 0.8rem; height: 32px;" value="{{ date('H:i') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold mb-1"
                                    style="font-size: 0.7rem;">Clinical Indication</label>
                                <textarea name="clinical_info" class="form-control border-0 bg-light-soft px-2"
                                    style="font-size: 0.8rem;" rows="2" placeholder="Describe indications..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-3 pb-3">
                        <button type="button" class="btn btn-light btn-sm px-3"
                            style="border-radius: 2px; font-size: 0.7rem; height: 32px;"
                            data-bs-dismiss="modal">DISMISS</button>
                        <button type="submit" class="btn btn-emerald btn-sm px-4 shadow-sm"
                            style="border-radius: 2px; font-size: 0.7rem; height: 32px;">AUTHORIZE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Image Modal (for quick thumbnail preview) --}}
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" style="background:rgba(0,0,0,0.95)">
            <div class="modal-content" style="background:transparent;border:none">
                <div class="modal-header border-0" style="position:absolute;top:0;right:0;z-index:10">
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" data-bs-dismiss="modal"
                    style="cursor:zoom-out">
                    <img id="modalImage" src=""
                        style="max-width:100%;max-height:100vh;object-fit:contain; box-shadow: 0 0 50px rgba(0,0,0,0.5)"
                        alt="DICOM Image">
                </div>
            </div>
        </div>
    </div>

    {{-- Viewer Modal (for full OHIF) --}}
    <div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-secondary py-2 px-3" style="background: #111;">
                    <h6 class="modal-title  text-white small"><i data-feather="monitor"
                            style="width:14px;height:14px;margin-right:8px" class="text-emerald"></i> DICOM DIAGNOSTIC
                        WORKSTATION</h6>
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

    <style>
        .text-emerald {
            color: #10b981 !important;
        }

        .bg-emerald {
            background-color: #10b981 !important;
            color: #fff !important;
        }

        .btn-emerald {
            background-color: #10b981;
            color: #fff;
            border: none;
            transition: all 0.2s;
        }

        .btn-emerald:hover {
            background-color: #059669;
            color: #fff;
            transform: translateY(-1px);
        }

        .bg-emerald-soft {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
        }

        .bg-light-soft {
            background-color: #f1f5f9;
        }

        .study-row:hover {
            background-color: #fafbfc;
        }

        .series-card:hover {
            transform: translateY(-3px);
            border-color: #10b981 !important;
            cursor: pointer;
        }

        .badge.bg-success {
            background-color: #10b981 !important;
        }

        .badge.bg-warning {
            background-color: #f59e0b !important;
        }

        .badge.bg-danger {
            background-color: #ef4444 !important;
        }

        .badge.bg-info {
            background-color: #0ea5e9 !important;
        }

        /* Tabs Styling */
        #patientDetailTabs .nav-link {
            transition: all 0.2s;
        }

        #patientDetailTabs .nav-link.active {
            color: #10b981 !important;
            background: transparent !important;
        }

        #patientDetailTabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #10b981;
            border-radius: 10px 10px 0 0;
        }

        #patientDetailTabs .nav-link:hover:not(.active) {
            color: #10b981 !important;
            opacity: 0.8;
        }
    </style>
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
            document.getElementById('viewerIframe').src = url;
            var viewerModal = new bootstrap.Modal(document.getElementById('viewerModal'));
            viewerModal.show();
        }

        document.getElementById('viewerModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('viewerIframe').src = '';
        });

        // Initialize Feather
        document.addEventListener('DOMContentLoaded', function () {
            feather.replace();
        });
    </script>
@endpush