@extends('layouts.app')
@section('title', 'PACS - Pasien')
@section('page-title', 'Pasien DICOM')

@section('content')
    <div class="card card-medizen">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">PACS Patient Registry</h5>
                <div class="text-muted small" style="font-size: 0.65rem;">DICOM DATA MANAGEMENT & SYNCHRONIZATION</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pacs.index') }}" class="btn btn-emerald-soft btn-sm px-3 fw-bold"
                    style="font-size: 0.7rem;">
                    <i data-feather="grid" class="me-1" style="width: 14px;"></i> PACS DASHBOARD
                </a>
                <a href="{{ route('pacs.sync-all-patients') }}" class="btn btn-dark btn-sm px-3 fw-bold swal-confirm"
                    data-swal-title="Full PACS Sync"
                    data-swal-text="Integrasikan seluruh data pasien PACS ke RIS? Proses ini memakan waktu."
                    data-swal-confirm-text="SYNC ALL" style="font-size: 0.7rem;">
                    <i data-feather="refresh-cw" class="me-1" style="width: 14px;"></i> FULL SYNC
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Search Area -->
            <div class="p-3 bg-light-soft border-bottom">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-10">
                        <div class="position-relative">
                            <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                                style="width: 14px; height: 14px;"></i>
                            <input type="text" name="search" class="form-control search-box-medizen ps-5"
                                placeholder="Cari berdasarkan nama pasien..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold py-2"
                            style="font-size: 0.7rem;">SEARCH DICOM</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>DICOM Identifier</th>
                            <th>Full Patient Name</th>
                            <th class="text-center">Birth Date</th>
                            <th class="text-center">Gender</th>
                            <th class="text-center">Studies</th>
                            <th class="text-end">Service Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $i => $p)
                            @php
                                $tags = $p['MainDicomTags'] ?? [];
                                $patientName = str_replace('^', ', ', $tags['PatientName'] ?? '-');
                                $birthDate = '-';
                                if (isset($tags['PatientBirthDate']) && $tags['PatientBirthDate'] && strlen($tags['PatientBirthDate']) == 8) {
                                    try {
                                        $birthDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['PatientBirthDate'])->format('d M Y');
                                    } catch (\Exception $e) {
                                    }
                                }
                                $sex = ($tags['PatientSex'] ?? '-') === 'M' ? 'MALE' : (($tags['PatientSex'] ?? '-') === 'F' ? 'FEMALE' : ($tags['PatientSex'] ?? '-'));
                            @endphp
                            <tr>
                                <td class="text-muted fw-bold">
                                    {{ str_pad((($page - 1) * $perPage) + $i + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <code class="text-emerald fw-bold"
                                        style="font-size: 0.7rem;">{{ $tags['PatientID'] ?? '-' }}</code>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $patientName }}</div>
                                </td>
                                <td class="text-center fw-bold text-slate-600">{{ $birthDate }}</td>
                                <td class="text-center">
                                    <span
                                        class="badge border px-2 py-1 {{ $sex == 'MALE' ? 'bg-primary-soft text-primary' : 'bg-danger-soft text-danger' }}"
                                        style="font-size: 0.6rem;">
                                        {{ $sex }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-emerald-soft text-emerald px-2 py-1"
                                        style="font-size: 0.7rem;">{{ count($p['Studies'] ?? []) }} EXAMS</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('pacs.patient-detail', $p['ID']) }}"
                                            class="btn btn-emerald-soft btn-sm p-1 border shadow-sm" title="Detailed Overview">
                                            <i data-feather="external-link" style="width: 14px; height: 14px;"></i>
                                        </a>
                                        <form action="{{ route('pacs.sync-patient', $p['ID']) }}" method="POST"
                                            class="d-inline swal-confirm" data-swal-title="Sync to RIS?"
                                            data-swal-text="Integrasikan data pasien DICOM ke database RIS?">
                                            @csrf
                                            <button type="submit" class="btn btn-dark btn-sm p-1 border shadow-sm"
                                                title="Integrate to RIS">
                                                <i data-feather="refresh-ccw" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('pacs.delete-patient', $p['ID']) }}" method="POST"
                                            class="d-inline swal-confirm" data-swal-title="DANGER: Delete Patient?"
                                            data-swal-text="Hapus pasien & SELURUH gambarnya dari PACS secara PERMANEN!">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger-soft btn-sm p-1 border shadow-sm"
                                                title="Delete from Storage">
                                                <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="database" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">PACS DATABASE EMPTY</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                    PAGE {{ $page }} • SHOWING {{ count($patients) }} RECORDS
                </div>
                <div class="d-flex gap-2">
                    @if($page > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
                            class="btn btn-white btn-sm px-3 fw-bold border">← PREVIOUS</a>
                    @endif
                    @if($hasMore)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
                            class="btn btn-white btn-sm px-3 fw-bold border">NEXT →</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection