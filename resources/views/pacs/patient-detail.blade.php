@extends('layouts.app')
@section('title', 'PACS - Detail Pasien')
@section('page-title', 'Detail Pasien DICOM')

@section('content')
    @php
        $tags = $patient['MainDicomTags'] ?? [];
        $patientName = str_replace('^', ', ', $tags['PatientName'] ?? '-');
        $birthDate = '-';
        if (isset($tags['PatientBirthDate']) && $tags['PatientBirthDate'] && strlen($tags['PatientBirthDate']) == 8) {
            try {
                $birthDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['PatientBirthDate'])->format('d/m/Y');
            } catch (\Exception $e) {
            }
        }
        $sex = ($tags['PatientSex'] ?? '-') === 'M' ? 'Laki-laki' : (($tags['PatientSex'] ?? '') === 'F' ? 'Perempuan' : ($tags['PatientSex'] ?? '-'));
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-1">{{ $patientName }}</h1>
            <small class="text-muted">Patient ID: <code>{{ $tags['PatientID'] ?? '-' }}</code> · {{ count($studies) }}
                studies</small>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('pacs.sync-patient', $patient['ID']) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm swal-confirm"
                    data-swal-title="RIS Synchronization"
                    data-swal-text="Integrasikan data demografi pasien DICOM ini ke dalam sistem rekam medis RIS?"
                    data-swal-confirm-text="Ya, Sync ke RIS">
                    <i data-feather="refresh-cw" style="width:14px;height:14px"></i> Sync ke RIS
                </button>
            </form>
            <form action="{{ route('pacs.delete-patient', $patient['ID']) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm swal-confirm"
                    data-swal-title="Purge Patient Record"
                    data-swal-text="PERHATIAN: Ini akan menghapus pasien beserta SELURUH data study dan gambarnya dari server PACS secara permanen. Lanjutkan?"
                    data-swal-type="error" data-swal-confirm-text="Ya, Hapus Permanen">
                    <i data-feather="trash-2" style="width:14px;height:14px"></i> Hapus Pasien
                </button>
            </form>
            <a href="{{ route('pacs.patients') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left"
                    style="width:14px;height:14px"></i> Kembali</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i data-feather="user" style="width:16px;height:16px;margin-right:6px"></i> Data
                    Pasien DICOM</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Patient ID</td>
                            <td><code>{{ $tags['PatientID'] ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td><strong>{{ $patientName }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Lahir</td>
                            <td>{{ $birthDate }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jenis Kelamin</td>
                            <td>{{ $sex }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Other Patient IDs</td>
                            <td>{{ $tags['OtherPatientIDs'] ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i data-feather="info" style="width:16px;height:16px;margin-right:6px"></i> Info
                    PACS</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.75rem">
                        <tr>
                            <td class="text-muted">PACS ID</td>
                            <td><code style="font-size:0.65rem">{{ $patient['ID'] ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Stability</td>
                            <td>{{ ($patient['IsStable'] ?? false) ? '✅ Stable' : '⏳ Unstable' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Update</td>
                            <td>{{ isset($patient['LastUpdate']) ? \Carbon\Carbon::parse($patient['LastUpdate'])->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Studies</td>
                            <td><strong>{{ count($studies) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i data-feather="folder" style="width:16px;height:16px;margin-right:6px"></i> Studies
                        ({{ count($studies) }})</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Study Date</th>
                                    <th>Description</th>
                                    <th>Accession</th>
                                    <th>Modality</th>
                                    <th>Series</th>
                                    <th>Size</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studies as $i => $s)
                                    @php
                                        $sTags = $s['MainDicomTags'] ?? [];
                                        $studyDate = '-';
                                        if (isset($sTags['StudyDate']) && $sTags['StudyDate'] && strlen($sTags['StudyDate']) == 8) {
                                            try {
                                                $studyDate = \Carbon\Carbon::createFromFormat('Ymd', $sTags['StudyDate'])->format('d/m/Y');
                                            } catch (\Exception $e) {
                                            }
                                        }
                                        $studyStats = $s['_statistics'] ?? null;
                                        $diskSize = '-';
                                        if ($studyStats) {
                                            $ds = $studyStats['DiskSize'] ?? 0;
                                            $diskSize = $ds > 1048576 ? number_format($ds / 1048576, 1) . ' MB' : number_format($ds / 1024, 0) . ' KB';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong>{{ $studyDate }}</strong></td>
                                        <td>{{ $sTags['StudyDescription'] ?? '-' }}</td>
                                        <td><code>{{ $sTags['AccessionNumber'] ?? '-' }}</code></td>
                                        <td class="text-center">
                                            <span class="badge bg-emerald-soft text-emerald px-2 py-1 border"
                                                style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">
                                                {{ $sTags['ModalitiesInStudy'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td><span class="badge bg-primary">{{ count($s['Series'] ?? []) }}</span></td>
                                        <td><small class="text-muted">{{ $diskSize }}</small></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('pacs.study-detail', $s['ID']) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Detail"><i data-feather="info"
                                                        style="width:12px;height:12px"></i></a>
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    title="DICOM Viewer"
                                                    onclick="openViewer('{{ app(\App\Services\PACSClient::class)->getOHIFViewerUrl($sTags['StudyInstanceUID'] ?? '') }}')"><i
                                                        data-feather="eye" style="width:12px;height:12px"></i></button>
                                                <a href="{{ url("/studies/{$s['ID']}/archive") }}"
                                                    class="btn btn-sm btn-outline-secondary" title="Download" target="_blank"><i
                                                        data-feather="download" style="width:12px;height:12px"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Tidak ada study</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection