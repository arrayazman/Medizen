@extends('layouts.app')
@section('title', 'DICOM Viewer')
@section('page-title', 'DICOM Viewer')

@section('content')
    @php
        $tags = $study['MainDicomTags'] ?? [];
        $pTags = $study['PatientMainDicomTags'] ?? [];
        $patientName = str_replace('^', ', ', $pTags['PatientName'] ?? 'Unknown');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-0"><i data-feather="eye" style="width:18px;height:18px;margin-right:6px"></i>
                {{ $patientName }}</h1>
            <small class="text-muted">{{ $tags['StudyDescription'] ?? 'Study' }} ·
                {{ $tags['AccessionNumber'] ?? '' }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Viewer selector tabs --}}
            {{-- Viewer indicator --}}
            <div class="btn btn-sm btn-outline-primary active pe-none">DICOM Viewer</div>
            <a href="{{ $baseUrl }}/studies/{{ $study['ID'] }}/archive" class="btn btn-outline-secondary btn-sm"
                target="_blank"><i data-feather="download" style="width:14px;height:14px"></i></a>
            <a href="{{ route('pacs.study-detail', $study['ID']) }}" class="btn btn-outline-secondary btn-sm">Detail</a>
            <a href="{{ route('pacs.studies') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <iframe id="viewerFrame" src="{{ $viewers['ohif'] }}"
                style="width:100%; height:82vh; border:none; border-radius:0 0 12px 12px; background:#000;"></iframe>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const viewerUrls = @json($viewers);

        function switchViewer(type, btn) {
            document.getElementById('viewerFrame').src = viewerUrls[type];
            document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>
@endpush