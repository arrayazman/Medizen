@extends('layouts.app')
@section('title', 'Detail Study DICOM')
@section('page-title', 'Detail Study DICOM')

@section('content')
    @php
        $tags = $study['MainDicomTags'] ?? [];
        $pTags = $study['PatientMainDicomTags'] ?? [];
        $patientName = str_replace('^', ', ', $pTags['PatientName'] ?? '-');
        $studyDate = '-';
        if (isset($tags['StudyDate']) && $tags['StudyDate'] && strlen($tags['StudyDate']) == 8) {
            try {
                $studyDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['StudyDate'])->format('d/m/Y');
            } catch (\Exception $e) {
            }
        }
        $studyTime = isset($tags['StudyTime']) ? substr($tags['StudyTime'], 0, 8) : '-';
    @endphp

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-1">{{ $tags['StudyDescription'] ?? 'Study Detail' }}</h1>
            <small class="text-muted">{{ $patientName }} · {{ $studyDate }} ·
                <code>{{ $tags['AccessionNumber'] ?? '-' }}</code></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Viewer Buttons --}}
            {{-- Viewer Button --}}
            <button type="button" class="btn btn-primary btn-sm"
                onclick="openViewer('{{ app(\App\Services\PACSClient::class)->getOHIFViewerUrl($studyUID) }}')">
                <i data-feather="monitor" style="width:14px;height:14px"></i> DICOM Viewer
            </button>
            {{-- Download Buttons --}}
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i
                        data-feather="download" style="width:14px;height:14px"></i> Download</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ url("/studies/{$study['ID']}/archive") }}"
                            target="_blank">Download ZIP (DICOM)</a></li>
                    <li><a class="dropdown-item" href="{{ url("/studies/{$study['ID']}/media") }}" target="_blank">Download
                            DICOMDIR</a></li>
                </ul>
            </div>
            {{-- Edit Tags --}}
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTagsModal"><i
                    data-feather="edit-3" style="width:14px;height:14px"></i> Edit Tags</button>
            <a href="{{ route('pacs.studies') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left Column: Patient + Study Info --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i data-feather="user" style="width:16px;height:16px;margin-right:6px"></i> Data Pasien</span>
                    <a href="{{ route('pacs.patient-detail', $study['ParentPatient'] ?? '') }}"
                        class="btn btn-sm btn-outline-primary">Lihat Pasien</a>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Patient ID</td>
                            <td><code>{{ $pTags['PatientID'] ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td><strong>{{ $patientName }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Lahir</td>
                            <td>{{ isset($pTags['PatientBirthDate']) && $pTags['PatientBirthDate'] && strlen($pTags['PatientBirthDate']) == 8 ? \Carbon\Carbon::createFromFormat('Ymd', $pTags['PatientBirthDate'])->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jenis Kelamin</td>
                            <td>{{ ($pTags['PatientSex'] ?? '-') === 'M' ? 'Laki-laki' : (($pTags['PatientSex'] ?? '') === 'F' ? 'Perempuan' : ($pTags['PatientSex'] ?? '-')) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i data-feather="folder" style="width:16px;height:16px;margin-right:6px"></i> Info
                    Study</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Study Date</td>
                            <td><strong>{{ $studyDate }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Study Time</td>
                            <td>{{ $studyTime }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Description</td>
                            <td><strong>{{ $tags['StudyDescription'] ?? '-' }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Accession No</td>
                            <td><code>{{ $tags['AccessionNumber'] ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Study ID</td>
                            <td>{{ $tags['StudyID'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Referring Physician</td>
                            <td>{{ str_replace('^', ', ', $tags['ReferringPhysicianName'] ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Institution</td>
                            <td>{{ $tags['InstitutionName'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Study Instance UID</td>
                            <td><code style="font-size:0.6rem;word-break:break-all">{{ $studyUID }}</code></td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($stats)
                <div class="card">
                    <div class="card-header"><i data-feather="bar-chart-2" style="width:16px;height:16px;margin-right:6px"></i>
                        Statistik</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 mb-0 text-primary">{{ $stats['CountSeries'] ?? 0 }}</div><small
                                    class="text-muted">Series</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0 text-primary">{{ $stats['CountInstances'] ?? 0 }}</div><small
                                    class="text-muted">Instances</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0 text-primary">
                                    @php
                                        $size = $stats['DiskSize'] ?? $stats['DiskSizeMB'] ?? 0;
                                        echo $size > 1048576 ? number_format($size / 1048576, 1) . ' MB' : number_format($size / 1024, 0) . ' KB';
                                    @endphp
                                </div>
                                <small class="text-muted">Size</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column: Series with Thumbnails --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i data-feather="layers" style="width:16px;height:16px;margin-right:6px"></i> Series
                        ({{ count($series) }})</span>
                </div>
                <div class="card-body p-0">
                    @forelse($series as $i => $sr)
                        @php
                            $srTags = $sr['MainDicomTags'] ?? [];
                            $firstInst = $sr['_firstInstance'] ?? null;
                            $instCount = $sr['_instanceCount'] ?? 0;
                        @endphp
                        <div class="d-flex align-items-center p-3 {{ $i > 0 ? 'border-top' : '' }}">
                            {{-- Thumbnail --}}
                            <div class="me-3" style="flex-shrink:0">
                                @if($firstInst)
                                    <a href="{{ route('pacs.series-detail', $sr['ID']) }}">
                                        <img src="{{ route('pacs.instance-preview', $firstInst) }}" alt="Preview" class="rounded"
                                            style="width:120px;height:90px;object-fit:cover;background:#1a1f2e;border:1px solid var(--border-color)"
                                            loading="lazy"
                                            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'90\'%3E%3Crect width=\'120\' height=\'90\' fill=\'%231a1f2e\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23666\' text-anchor=\'middle\' dy=\'.3em\' font-size=\'12\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                                    </a>
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center"
                                        style="width:120px;height:90px;background:#1a1f2e;border:1px solid var(--border-color)">
                                        <i data-feather="image" style="color:#666;width:32px;height:32px"></i>
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $srTags['SeriesDescription'] ?? 'Series ' . ($srTags['SeriesNumber'] ?? ($i + 1)) }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ $srTags['Modality'] ?? '-' }}</span>
                                    </div>
                                    <span class="badge bg-primary">{{ $instCount }} gambar</span>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;margin-top:4px">
                                    @if(isset($srTags['BodyPartExamined']))<span class="me-3">🦴
                                    {{ $srTags['BodyPartExamined'] }}</span>@endif
                                    @if(isset($srTags['ProtocolName']))<span class="me-3">📋
                                    {{ $srTags['ProtocolName'] }}</span>@endif
                                    @if(isset($srTags['SeriesNumber']))<span>No. {{ $srTags['SeriesNumber'] }}</span>@endif
                                </div>
                                <div class="mt-2 d-flex gap-1">
                                    <a href="{{ route('pacs.series-detail', $sr['ID']) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i data-feather="grid" style="width:12px;height:12px"></i> Lihat Gambar
                                    </a>
                                    <a href="{{ $baseUrl }}/series/{{ $sr['ID'] }}/archive"
                                        class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i data-feather="download" style="width:12px;height:12px"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Tidak ada series</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Tags Modal --}}
    <div class="modal fade" id="editTagsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editTagsForm" method="POST" action="{{ route('pacs.modify-study', $study['ID']) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i data-feather="edit-3"
                                style="width:18px;height:18px;margin-right:8px"></i> Edit DICOM Tags</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" style="font-size:0.8rem">
                            <i data-feather="alert-triangle" style="width:14px;height:14px;margin-right:6px"></i>
                            <strong>Perhatian:</strong> Mengubah DICOM tags akan memodifikasi data study. Study asli akan
                            diganti dengan versi baru.
                        </div>

                        <h6 class="mb-3 border-bottom pb-2 text-primary">Data Pasien</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nama Pasien</label>
                                <input type="text" name="PatientName" class="form-control"
                                    value="{{ $pTags['PatientName'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Patient ID</label>
                                <input type="text" name="PatientID" class="form-control"
                                    value="{{ $pTags['PatientID'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="PatientBirthDate" class="form-control"
                                    value="{{ isset($pTags['PatientBirthDate']) && strlen($pTags['PatientBirthDate']) == 8 ? \Carbon\Carbon::createFromFormat('Ymd', $pTags['PatientBirthDate'])->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="PatientSex" class="form-select">
                                    <option value="">- Pilih -</option>
                                    <option value="M" {{ ($pTags['PatientSex'] ?? '') === 'M' ? 'selected' : '' }}>Laki-laki
                                        (M)</option>
                                    <option value="F" {{ ($pTags['PatientSex'] ?? '') === 'F' ? 'selected' : '' }}>Perempuan
                                        (F)</option>
                                    <option value="O" {{ ($pTags['PatientSex'] ?? '') === 'O' ? 'selected' : '' }}>Other (O)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3 border-bottom pb-2 text-primary">Data Study</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Study Description</label>
                                <input type="text" name="StudyDescription" class="form-control"
                                    value="{{ $tags['StudyDescription'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Accession Number</label>
                                <input type="text" name="AccessionNumber" class="form-control"
                                    value="{{ $tags['AccessionNumber'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Study ID</label>
                                <input type="text" name="StudyID" class="form-control" value="{{ $tags['StudyID'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Study Date</label>
                                <input type="date" name="StudyDate" class="form-control"
                                    value="{{ isset($tags['StudyDate']) && strlen($tags['StudyDate']) == 8 ? \Carbon\Carbon::createFromFormat('Ymd', $tags['StudyDate'])->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Study Time (HHMMSS)</label>
                                <input type="text" name="StudyTime" class="form-control"
                                    value="{{ $tags['StudyTime'] ?? '' }}" placeholder="HHMMSS">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referring Physician</label>
                                <input type="text" name="ReferringPhysicianName" class="form-control"
                                    value="{{ $tags['ReferringPhysicianName'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Institution Name</label>
                                <input type="text" name="InstitutionName" class="form-control"
                                    value="{{ $tags['InstitutionName'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveTags">
                            <i data-feather="save" id="iconSave" style="width:14px;height:14px"></i>
                            <span id="spinnerSave" class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <span id="textSave">Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('editTagsForm');
                const btn = document.getElementById('btnSaveTags');
                const text = document.getElementById('textSave');
                const spinner = document.getElementById('spinnerSave');
                const icon = document.getElementById('iconSave');

                if (form) {
                    form.addEventListener('submit', function (e) {
                        if (!confirm('Yakin ingin memodifikasi DICOM tags study ini?')) {
                            e.preventDefault();
                            return false;
                        }

                        // Show loading state
                        btn.disabled = true;
                        text.innerText = 'Menyimpan...';
                        spinner.classList.remove('d-none');
                        if (icon) icon.classList.add('d-none');
                    });
                }
            });
        </script>
    @endpush

@endsection