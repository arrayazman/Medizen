@extends('layouts.app')
@section('title', 'PACS Server')
@section('page-title', 'PACS Infrastructure Node')

@section('content')
    {{-- Connection Status Alert --}}
    <div class="alert {{ $isAvailable ? 'bg-emerald-soft border-emerald' : 'bg-danger-soft border-danger' }} d-flex align-items-center mb-4 shadow-sm"
        style="border-radius: 6px;">
        <div class="me-3 d-flex align-items-center justify-content-center"
            style="width: 32px; height: 32px; border-radius: 50%; background: {{ $isAvailable ? '#10b981' : '#ef4444' }}; color: #fff;">
            <i data-feather="{{ $isAvailable ? 'check' : 'x' }}" style="width:18px; height:18px;"></i>
        </div>
        <div class="flex-grow-1">
            @if($isAvailable)
                <div class="fw-bold text-emerald" style="font-size: 0.85rem;">PACS NODE ONLINE</div>
                <div class="text-emerald opacity-75 small" style="font-size: 0.75rem;">CONNECTED TO:
                    <code>{{ config('pacs.url') }}</code> | BUILD: {{ $system['Version'] ?? 'UNKNOWN' }}
                </div>
            @else
                <div class="fw-bold text-danger" style="font-size: 0.85rem;">PACS NODE UNREACHABLE</div>
                <div class="text-danger opacity-75 small" style="font-size: 0.75rem;">CHECK NETWORK CONFIGURATION FOR:
                    <code>{{ config('pacs.url') }}</code>
                </div>
            @endif
        </div>
    </div>

    @if($isAvailable)
        {{-- Statistics Grid --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="{{ route('pacs.patients') }}" class="text-decoration-none">
                    <div class="stat-card-medizen">
                        <div class="stat-icon-medizen bg-emerald-soft"><i data-feather="users" style="width: 18px;"></i></div>
                        <div class="stat-value-medizen">{{ number_format($statistics['CountPatients'] ?? 0) }}</div>
                        <div class="stat-label-medizen mt-1">Total DICOM Patients</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ route('pacs.studies') }}" class="text-decoration-none">
                    <div class="stat-card-medizen">
                        <div class="stat-icon-medizen bg-blue-soft"><i data-feather="folder" style="width: 18px;"></i></div>
                        <div class="stat-value-medizen">{{ number_format($statistics['CountStudies'] ?? 0) }}</div>
                        <div class="stat-label-medizen mt-1">Examination Studies</div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="stat-card-medizen">
                    <div class="stat-icon-medizen bg-orange-soft"><i data-feather="layers" style="width: 18px;"></i></div>
                    <div class="stat-value-medizen">{{ number_format($statistics['CountSeries'] ?? 0) }}</div>
                    <div class="stat-label-medizen mt-1">Image Series</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-medizen">
                    <div class="stat-icon-medizen bg-purple-soft"><i data-feather="image" style="width: 18px;"></i></div>
                    <div class="stat-value-medizen">{{ number_format($statistics['CountInstances'] ?? 0) }}</div>
                    <div class="stat-label-medizen mt-1">DICOM Instances</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Node Configuration --}}
            <div class="col-lg-7">
                <div class="card card-medizen h-100">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold text-slate-800" style="font-size: 0.9rem;"><i data-feather="cpu" class="me-2"
                                style="width: 16px;"></i> Node Configuration Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 0.75rem;">
                                <tbody>
                                    <tr class="border-bottom border-light">
                                        <td class="py-2 text-muted fw-bold" width="40%">CORE VERSION</td>
                                        <td class="py-2 fw-bold text-slate-800">{{ $system['Version'] ?? '-' }}</td>
                                    </tr>
                                    <tr class="border-bottom border-light">
                                        <td class="py-2 text-muted fw-bold">API PROTOCOL</td>
                                        <td class="py-2 fw-bold text-slate-600">v{{ $system['ApiVersion'] ?? '-' }}</td>
                                    </tr>
                                    <tr class="border-bottom border-light">
                                        <td class="py-2 text-muted fw-bold">DATABASE SCHEMA</td>
                                        <td class="py-2 fw-bold text-slate-600">{{ $system['DatabaseVersion'] ?? '-' }}</td>
                                    </tr>
                                    <tr class="border-bottom border-light">
                                        <td class="py-2 text-muted fw-bold">NODE AET (AE TITLE)</td>
                                        <td class="py-2"><code
                                                class="text-emerald fw-bold">{{ $system['DicomAet'] ?? '-' }}</code></td>
                                    </tr>
                                    <tr class="border-bottom border-light">
                                        <td class="py-2 text-muted fw-bold">DICOM PORT</td>
                                        <td class="py-2 fw-bold text-slate-600">{{ $system['DicomPort'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-muted fw-bold">DISK UTILIZATION</td>
                                        <td class="py-2 fw-bold text-slate-800">
                                            @php
                                                $sizeBytes = $statistics['TotalDiskSizeMB'] ?? $statistics['TotalDiskSize'] ?? 0;
                                                if ($sizeBytes > 1073741824)
                                                    echo number_format($sizeBytes / 1073741824, 2) . ' GB';
                                                elseif ($sizeBytes > 1048576)
                                                    echo number_format($sizeBytes / 1048576, 1) . ' MB';
                                                else
                                                    echo number_format($sizeBytes / 1024, 0) . ' KB';
                                            @endphp
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 p-3 bg-light-soft rounded border">
                            <h6 class="fw-bold mb-2 text-slate-700" style="font-size: 0.75rem;">System Capabilities</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    class="badge border {{ ($system['PluginsEnabled'] ?? false) ? 'bg-emerald-soft text-emerald' : 'bg-light text-muted' }} px-2 py-1"
                                    style="font-size: 0.6rem;">PLUGINS:
                                    {{ ($system['PluginsEnabled'] ?? false) ? 'ENABLED' : 'DISABLED' }}</span>
                                <span
                                    class="badge border {{ ($system['StorageCompression'] ?? false) ? 'bg-emerald-soft text-emerald' : 'bg-light text-muted' }} px-2 py-1"
                                    style="font-size: 0.6rem;">COMPRESSION:
                                    {{ ($system['StorageCompression'] ?? false) ? 'ACTIVE' : 'OFF' }}</span>
                                <span class="badge border bg-light text-muted px-2 py-1" style="font-size: 0.6rem;">HTTP PORT:
                                    {{ $system['HttpPort'] ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resources & Modalities --}}
            <div class="col-lg-5">
                {{-- DICOM Modalities --}}
                <div class="card card-medizen mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-slate-800" style="font-size: 0.9rem;"><i data-feather="radio" class="me-2"
                                style="width: 16px;"></i> Active Modalities</h6>
                        <div class="d-flex align-items-center gap-2">
                           <a href="{{ route('pacs.modalities') }}" class="btn btn-emerald-soft btn-xs fw-bold px-2 py-1" style="font-size: 0.6rem;">MANAGE</a>
                           <span class="badge bg-light-soft text-slate-600 border">{{ count($modalities ?? []) }} ENTRIES</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.7rem;">
                                <tbody>
                                    @forelse($modalities ?? [] as $mod)
                                        <tr>
                                            <td class="px-3 py-2"><code class="text-slate-500">{{ $mod }}</code></td>
                                            <td class="px-3 py-2 text-end text-emerald fw-bold">AUTHORIZED</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-3 py-4 text-center text-muted fst-italic">No DICOM modalities discovered
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Plugins --}}
                <div class="card card-medizen">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-slate-800" style="font-size: 0.9rem;"><i data-feather="package"
                                class="me-2" style="width: 16px;"></i> Node Plugins</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.7rem;">
                                <tbody>
                                    @forelse($plugins ?? [] as $plugin)
                                        <tr>
                                            <td class="px-3 py-2 fw-bold text-slate-700">{{ $plugin }}</td>
                                            <td class="px-3 py-2 text-end"><span class="badge bg-emerald-soft text-emerald px-2"
                                                    style="font-size: 0.6rem;">LOADED</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-3 py-4 text-center text-muted fst-italic">No external plugins loaded</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Management Links --}}
        <div class="row g-3 mt-3">
            <div class="col-md-3">
                <a href="{{ route('pacs.search') }}"
                    class="btn btn-light border w-100 py-2 fw-bold d-flex align-items-center justify-content-center"
                    style="font-size: 0.75rem;">
                    <i data-feather="search" class="me-2" style="width: 14px;"></i> ADVANCED DICOM SEARCH
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ url('/app/explorer.html') }}" target="_blank"
                    class="btn btn-light border w-100 py-2 fw-bold d-flex align-items-center justify-content-center"
                    style="font-size: 0.75rem;">
                    <i data-feather="external-link" class="me-2" style="width: 14px;"></i> NATIVE PACS EXPLORER
                </a>
            </div>
        </div>
    @endif
@endsection