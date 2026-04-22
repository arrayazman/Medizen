@extends('layouts.app')
@section('title', 'Detail Series DICOM')
@section('page-title', 'Detail Series DICOM')

@section('content')
    @php
        $srTags = $seriesData['MainDicomTags'] ?? [];
        $studyTags = $study ? ($study['MainDicomTags'] ?? []) : [];
        $pTags = $study ? ($study['PatientMainDicomTags'] ?? []) : [];
        $patientName = str_replace('^', ', ', $pTags['PatientName'] ?? '-');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-1">{{ $srTags['SeriesDescription'] ?? 'Series ' . ($srTags['SeriesNumber'] ?? '') }}</h1>
            <small class="text-muted">
                {{ $patientName }} · {{ $studyTags['StudyDescription'] ?? '' }} ·
                <span class="badge bg-secondary">{{ $srTags['Modality'] ?? '-' }}</span>
                <span class="badge bg-primary">{{ count($instances) }} gambar</span>
            </small>
        </div>
        <div class="d-flex gap-2">
            @if($study)
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="openViewer('{{ app(\App\Services\PACSClient::class)->getOHIFViewerUrl($studyTags['StudyInstanceUID'] ?? '') }}')">
                    <i data-feather="eye" style="width:14px;height:14px"></i> DICOM Viewer
                </button>
                <a href="{{ route('pacs.study-detail', $study['ID']) }}" class="btn btn-outline-secondary btn-sm">Kembali ke
                    Study</a>
            @endif
            <a href="{{ url("/series/{$seriesData['ID']}/archive") }}" class="btn btn-outline-secondary btn-sm"
                target="_blank"><i data-feather="download" style="width:14px;height:14px"></i> Download</a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Series Info --}}
        <div class="col-lg-3">
            <div class="card mb-3">
                <div class="card-header">Info Series</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.8rem">
                        <tr>
                            <td class="text-muted">Modality</td>
                            <td><span class="badge bg-secondary">{{ $srTags['Modality'] ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Series No</td>
                            <td>{{ $srTags['SeriesNumber'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Description</td>
                            <td>{{ $srTags['SeriesDescription'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Body Part</td>
                            <td>{{ $srTags['BodyPartExamined'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Protocol</td>
                            <td>{{ $srTags['ProtocolName'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Manufacturer</td>
                            <td>{{ $srTags['Manufacturer'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Station</td>
                            <td>{{ $srTags['StationName'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Instances</td>
                            <td><strong>{{ count($instances) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Image Grid --}}
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i data-feather="grid" style="width:16px;height:16px;margin-right:6px"></i> Gambar DICOM
                        ({{ count($instances) }})</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" onclick="setGridSize(4)" id="grid-4">
                            <i data-feather="grid" style="width:14px;height:14px"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setGridSize(6)" id="grid-6">
                            <i data-feather="maximize" style="width:14px;height:14px"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setGridSize(3)" id="grid-3">
                            <i data-feather="minimize" style="width:14px;height:14px"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2" id="imageGrid">
                        @foreach($instances as $idx => $inst)
                            @php $instTags = $inst['MainDicomTags'] ?? []; @endphp
                            <div class="col-md-3 grid-item">
                                <div class="dicom-thumb"
                                    onclick="openImageModal('{{ route('pacs.instance-preview', $inst['ID']) }}', {{ $idx }})">
                                    <img src="{{ route('pacs.instance-preview', $inst['ID']) }}"
                                        alt="Instance {{ $instTags['InstanceNumber'] ?? ($idx + 1) }}" loading="lazy"
                                        onerror="this.parentElement.innerHTML='<div class=\'no-preview\'><i data-feather=\'image\'></i></div>';feather.replace()">
                                    <div class="thumb-label">
                                        #{{ $instTags['InstanceNumber'] ?? ($idx + 1) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(empty($instances))
                        <div class="text-center text-muted py-4">Tidak ada gambar dalam series ini</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Fullscreen Image Modal --}}
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen" style="background:rgba(0,0,0,0.95)">
            <div class="modal-content" style="background:transparent;border:none">
                <div class="modal-header border-0" style="position:absolute;top:0;right:0;z-index:10">
                    <div class="d-flex gap-2 align-items-center">
                        <span class="text-white" id="imageCounter" style="font-size:0.85rem"></span>
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="prevImage()"><i
                                data-feather="chevron-left" style="width:16px;height:16px"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="nextImage()"><i
                                data-feather="chevron-right" style="width:16px;height:16px"></i></button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" onclick="nextImage()">
                    <img id="modalImage" src="" style="max-width:100%;max-height:100vh;object-fit:contain"
                        alt="DICOM Image">
                </div>
            </div>
        </div>
    </div>

    {{-- Fullscreen DICOM Viewer Modal moved to layout --}}
@endsection

@push('styles')
    <style>
        .dicom-thumb {
            position: relative;
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            background: #1a1f2e;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            aspect-ratio: 4/3;
        }

        .dicom-thumb:hover {
            border-color: var(--primary);
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(15, 157, 88, 0.2);
        }

        .dicom-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .dicom-thumb .thumb-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2px 8px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
            color: #fff;
            font-size: 0.7rem;
            text-align: right;
        }

        .dicom-thumb .no-preview {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const images = @json(collect($instances)->pluck('ID')->map(fn($id) => route('pacs.instance-preview', $id)));
        let currentIdx = 0;

        function openImageModal(src, idx) {
            currentIdx = idx;
            document.getElementById('modalImage').src = src;
            updateCounter();
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function nextImage() {
            if (currentIdx < images.length - 1) {
                currentIdx++;
                document.getElementById('modalImage').src = images[currentIdx];
                updateCounter();
            }
        }

        function prevImage() {
            if (currentIdx > 0) {
                currentIdx--;
                document.getElementById('modalImage').src = images[currentIdx];
                updateCounter();
            }
        }

        function updateCounter() {
            document.getElementById('imageCounter').textContent = (currentIdx + 1) + ' / ' + images.length;
        }

        function setGridSize(cols) {
            document.querySelectorAll('.grid-item').forEach(el => {
                el.className = 'col-md-' + (12 / cols) + ' grid-item';
            });
            document.querySelectorAll('[id^=grid-]').forEach(b => b.classList.remove('active'));
            document.getElementById('grid-' + cols)?.classList.add('active');
        }

        // Global openViewer handles the DICOM viewer modal.

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (document.getElementById('imageModal').classList.contains('show')) {
                if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); nextImage(); }
                if (e.key === 'ArrowLeft') { e.preventDefault(); prevImage(); }
                if (e.key === 'Escape') { bootstrap.Modal.getInstance(document.getElementById('imageModal')).hide(); }
            }
        });
    </script>
@endpush