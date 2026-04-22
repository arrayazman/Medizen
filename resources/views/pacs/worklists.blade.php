@extends('layouts.app')
@section('title', 'PACS - Worklists')
@section('page-title', 'DICOM Worklist (SCU/SCP)')

@section('content')
    <div class="card card-medizen">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Modality Worklist Registry</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">DICOM WORKLIST MANAGEMENT SYSTEM</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pacs.index') }}" class="btn btn-emerald-soft btn-sm px-3 fw-bold"
                    style="font-size: 0.7rem;">
                    <i data-feather="grid" class="me-1" style="width: 14px;"></i> PACS DASHBOARD
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filter Bar -->
            <div class="p-3 bg-light-soft border-bottom">
                <form method="GET" id="worklistFilterForm" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="text" name="search" id="filterSearch" class="form-control filter-box-medizen"
                            placeholder="Patient name or Accession..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
                            <input type="date" name="date_start" id="filterDateStart" class="form-control border-start-0" value="{{ request('date_start') }}">
                            <span class="input-group-text bg-light px-2" style="font-size: 0.7rem;">s/d</span>
                            <input type="date" name="date_end" id="filterDateEnd" class="form-control" value="{{ request('date_end') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="modality" id="filterModality" class="form-select filter-box-medizen">
                            <option value="">Modality: ALL</option>
                            @foreach(['CT', 'MR', 'CR', 'DR', 'DX', 'US', 'XA', 'MG', 'NM', 'PT', 'RF', 'SC', 'OT'] as $m)
                                <option value="{{ $m }}" {{ request('modality') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-dark btn-sm px-3 fw-bold py-2"
                            style="font-size: 0.7rem;">APPLY</button>
                        <a href="?reset=1" class="btn btn-light btn-sm border fw-bold ms-1 py-2"
                            style="font-size: 0.7rem;">RESET</a>
                    </div>
                </form>
            </div>

            @if(!$isAvailable)
                <div class="alert alert-danger m-3 border-0 shadow-sm align-items-center d-flex">
                    <i data-feather="alert-octagon" class="me-2"></i>
                    PACS Server is currently unreachable. Check your network configuration.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Patient Name</th>
                            <th>Accession</th>
                            <th class="text-center">Modality</th>
                            <th class="text-center">Scheduled Date</th>
                            <th>Filename</th>
                            <th class="text-end">Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($worklists as $i => $wl)
                            <tr>
                                <td class="text-muted fw-bold">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $wl['patient_name'] }}</div>
                                </td>
                                <td><code class="text-emerald fw-bold">{{ $wl['accession'] }}</code></td>
                                <td class="text-center">
                                    <span class="badge bg-emerald-soft text-emerald px-2 py-1 border"
                                        style="font-size: 0.6rem;">
                                        {{ $wl['modality'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($wl['date'] && $wl['date'] !== '-')
                                        <div class="fw-bold text-slate-800" style="font-size: 0.8rem;">
                                            {{ \Carbon\Carbon::parse($wl['date'])->format('d M Y') }}
                                        </div>
                                        @if(isset($wl['time']) && $wl['time'] !== '000000')
                                            <div class="text-muted" style="font-size: 0.65rem;">
                                                {{ \Carbon\Carbon::parse($wl['time'])->format('H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted" style="font-size: 0.65rem;">{{ $wl['filename'] }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button"
                                            class="btn btn-dark-soft btn-sm p-1 border shadow-sm view-wl-detail"
                                            data-json="{{ json_encode($wl['raw']) }}" title="MetaData Detail">
                                            <i data-feather="monitor" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <a href="{{ url("/worklists/{$wl['filename']}") }}" download
                                            class="btn btn-emerald-soft btn-sm p-1 border shadow-sm" title="Download .wl file">
                                            <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="clipboard" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">NO ACTIVE WORKLISTS</h6>
                                        <p class="small mb-0">Modality worklist queue is empty.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                    STATUS: {{ $isAvailable ? 'PACS CONNECTED' : 'DISCONNECTED' }} • TOTAL {{ count($worklists) }} ENTRIES
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL MAXIMALIST --}}
    <div class="modal fade" id="modalWlDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden; background: #f8fafc;">
                <div class="modal-header border-0 bg-white" style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); z-index: 10;">
                    <div>
                        <h5 class="modal-title fw-bolder text-slate-800 mb-0" style="letter-spacing: -0.2px;">WORKLIST METADATA
                        </h5>
                        <p class="text-emerald fw-bold mb-0" style="font-size: 0.65rem; letter-spacing: 1px;">DICOM TAGS EXPLORER SUMMARY</p>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 custom-scrollbar">
                    <div id="wl-detail-content" class="container-fluid p-0">
                        {{-- Content will be dynamically injected --}}
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-dark w-100 fw-bold py-2 border-0" data-bs-dismiss="modal"
                        style="border-radius: 8px; font-size: 0.75rem;">CLOSE PANEL</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .btn-dark-soft {
                background: #f8fafc;
                color: #475569;
                border: 1px solid #e2e8f0;
            }

            .btn-emerald-soft {
                background: #f0fdf4 !important;
                color: #10b981 !important;
                border: 1px solid #bbf7d0 !important;
            }

            .wl-meta-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 15px;
                margin-bottom: 6px;
                background: #f8fafc;
                border-radius: 8px;
                border-left: 3px solid #e2e8f0;
                transition: all 0.2s ease;
            }

            .wl-meta-card:hover {
                background: #f1f5f9;
                border-left-color: #10b981;
                transform: translateX(3px);
            }

            .wl-meta-key {
                font-size: 0.6rem;
                color: #94a3b8;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .wl-meta-val {
                font-size: 0.75rem;
                color: #1e293b;
                font-weight: 700;
                text-align: right;
                word-break: break-all;
                max-width: 65%;
            }

            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function () {
                // VIEW DETAIL MODAL
                $('.view-wl-detail').on('click', function () {
                    const data = $(this).data('json');
                    let html = '';

                    const knownTags = [
                        'PatientName', 'PatientID', 'PatientBirthDate', 'PatientSex',
                        'AccessionNumber', 'StudyDescription', 'Modality', 'ScheduledProcedureStepStartDate',
                        'ScheduledProcedureStepStartTime', 'ScheduledPerformingPhysicianName', 'RequestedProcedureDescription',
                        'ScheduledStationAETitle'
                    ];

                    const processData = (obj, prefix = '') => {
                        Object.keys(obj).forEach(key => {
                            if (key === 'Tags' && typeof obj[key] === 'object') {
                                processData(obj[key], '');
                            } else if (Array.isArray(obj[key]) && obj[key].length > 0 && typeof obj[key][0] === 'object') {
                                obj[key].forEach((item, index) => {
                                    processData(item, prefix + key + `[${index}] > `);
                                });
                            } else if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
                                processData(obj[key], prefix + key + ' > ');
                            } else if (obj[key] !== null && obj[key] !== '') {
                                let label = key.replace(/([A-Z])/g, ' $1').trim();
                                let isHighlight = knownTags.some(t => key.includes(t));
                                let valString = obj[key];
                                if(Array.isArray(valString)) { valString = valString.join(', '); }
                                
                                let cardClass = isHighlight ? 'bg-white border-success shadow-sm border-start border-3 border-end-0 border-top-0 border-bottom-0' : 'bg-white border text-muted opacity-75';

                                html += `<div class="col-xl-3 col-lg-4 col-md-6 mb-1">
                                    <div class="px-2 py-1 ${cardClass} h-100 d-flex flex-column justify-content-center" style="border-radius: 4px;">
                                        <div class="fw-bold mb-0 text-truncate" style="font-size:0.55rem; color:#64748b; letter-spacing:0.5px;" title="${prefix}${label}">${prefix}${label}</div>
                                        <div class="fw-bolder text-dark text-truncate" style="font-family: monospace; font-size:0.75rem;" title="${String(valString).replace(/\^/g, ' ')}">${String(valString).replace(/\^/g, ' ')}</div>
                                    </div>
                                </div>`;
                            }
                        });
                    };

                    processData(data);
                    $('#wl-detail-content').html(`<div class="row g-2">${html}</div>`);
                    $('#modalWlDetail').modal('show');
                });
            });
        </script>
    @endpush
@endsection