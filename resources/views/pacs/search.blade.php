@extends('layouts.app')
@section('title', 'PACS - Cari Study')
@section('page-title', 'Pencarian Study DICOM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i data-feather="search" style="width:18px;height:18px;margin-right:6px"></i> Pencarian Study
            DICOM</h1>
        <a href="{{ route('pacs.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left"
                style="width:14px;height:14px"></i> Dashboard</a>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i data-feather="filter" style="width:14px;height:14px;margin-right:6px"></i> Filter
            Pencarian</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pacs.search') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nama Pasien</label>
                        <input type="text" name="patient_name" class="form-control form-control-sm"
                            value="{{ request('patient_name') }}" placeholder="Nama pasien...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Patient ID</label>
                        <input type="text" name="patient_id" class="form-control form-control-sm"
                            value="{{ request('patient_id') }}" placeholder="ID pasien...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Study Description</label>
                        <input type="text" name="description" class="form-control form-control-sm"
                            value="{{ request('description') }}" placeholder="Deskripsi study...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Accession Number</label>
                        <input type="text" name="accession" class="form-control form-control-sm"
                            value="{{ request('accession') }}" placeholder="Accession...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" name="study_date_from" class="form-control form-control-sm"
                            value="{{ request('study_date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" name="study_date_to" class="form-control form-control-sm"
                            value="{{ request('study_date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Modalitas</label>
                        <select name="modality" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach(['CT', 'MR', 'CR', 'DR', 'DX', 'US', 'XA', 'MG', 'NM', 'PT', 'RF', 'SC', 'OT'] as $m)
                                <option value="{{ $m }}" {{ request('modality') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button class="btn btn-primary btn-sm"><i data-feather="search" style="width:14px;height:14px"></i>
                            Cari</button>
                        @if($searched)<a href="{{ route('pacs.search') }}"
                        class="btn btn-outline-secondary btn-sm">Reset</a>@endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($searched)
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i data-feather="folder" style="width:14px;height:14px;margin-right:6px"></i> Hasil Pencarian</span>
                <span class="badge bg-secondary">{{ count($studies) }} ditemukan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient ID</th>
                                <th>Nama Pasien</th>
                                <th>Study Date</th>
                                <th>Description</th>
                                <th>Accession</th>
                                <th>Modality</th>
                                <th>Series</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studies as $i => $s)
                                @php
                                    $tags = $s['MainDicomTags'] ?? [];
                                    $pTags = $s['PatientMainDicomTags'] ?? [];
                                    $patientName = str_replace('^', ', ', $pTags['PatientName'] ?? '-');
                                    $studyDate = '-';
                                    if (isset($tags['StudyDate']) && $tags['StudyDate'] && strlen($tags['StudyDate']) == 8) {
                                        try {
                                            $studyDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['StudyDate'])->format('d/m/Y');
                                        } catch (\Exception $e) {
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><code>{{ $pTags['PatientID'] ?? '-' }}</code></td>
                                    <td><strong>{{ $patientName }}</strong></td>
                                    <td>{{ $studyDate }}</td>
                                    <td>{{ $tags['StudyDescription'] ?? '-' }}</td>
                                    <td><code>{{ $tags['AccessionNumber'] ?? '-' }}</code></td>
                                    <td>
                                        <span class="badge bg-emerald-soft text-emerald px-2 py-1 border"
                                            style="font-size: 0.6rem; border-color: rgba(16, 185, 129, 0.2) !important;">
                                            {{ $tags['ModalitiesInStudy'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-soft text-primary px-2 py-1 border"
                                            style="font-size: 0.6rem;">
                                            {{ count($s['Series'] ?? []) }} SERS
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('pacs.study-detail', $s['ID']) }}"
                                                class="btn btn-sm btn-outline-primary" title="Detail"><i data-feather="info"
                                                    style="width:12px;height:12px"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="DICOM Viewer"
                                                onclick="openViewer('{{ app(\App\Services\PACSClient::class)->getOHIFViewerUrl($tags['StudyInstanceUID'] ?? '') }}')"><i
                                                    data-feather="eye" style="width:12px;height:12px"></i></button>
                                            <a href="{{ url("/studies/{$s['ID']}/archive") }}"
                                                class="btn btn-sm btn-outline-secondary" title="Download" target="_blank"><i
                                                    data-feather="download" style="width:12px;height:12px"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada hasil</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i data-feather="search" style="width:48px;height:48px;opacity:0.3;margin-bottom:1rem"></i>
                <h5>Masukkan Kriteria Pencarian</h5>
                <p>Gunakan filter di atas untuk mencari study DICOM berdasarkan nama pasien, tanggal, modalitas, dan lainnya.
                </p>
            </div>
        </div>
    @endif
@endsection