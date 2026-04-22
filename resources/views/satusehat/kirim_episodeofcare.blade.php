@extends('layouts.app')

@section('page-title', 'Bridging EpisodeOfCare SATUSEHAT')

@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom rounded-0">
        <div>
            <h5 class="mb-0 fw-bold text-slate-800" style="font-size: 1.1rem;">Bridging EpisodeOfCare</h5>
            <div class="text-muted small" style="font-size: 0.65rem;">SATUSEHAT INTEGRATION FOR PATIENT EPISODE OF CARE</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm px-3 shadow-none fw-bold rounded-0" style="font-size: 0.7rem;"
                type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i data-feather="filter" class="me-2" style="width: 14px;"></i> SEARCH & FILTER
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="collapse show p-1 bg-light-soft border-bottom" id="filterCollapse">
            <form action="{{ route('satusehat.kirim-episodeofcare') }}" method="GET" class="p-2">
                <div class="row g-1">
                    <div class="col-md-2 col-6">
                        <input type="date" name="tgl1" class="form-control form-control-sm rounded-0" value="{{ $tgl1 }}" style="font-size: 0.6rem;">
                    </div>
                    <div class="col-md-2 col-6">
                        <input type="date" name="tgl2" class="form-control form-control-sm rounded-0" value="{{ $tgl2 }}" style="font-size: 0.6rem;">
                    </div>
                    <div class="col-md-6 col-12">
                        <input type="text" name="keyword" class="form-control form-control-sm rounded-0" placeholder="RM / No. Rawat / Nama" value="{{ $keyword }}" style="font-size: 0.6rem;">
                    </div>
                    <div class="col-md-2 col-12">
                        <button type="submit" class="btn btn-dark btn-sm fw-bold rounded-0 w-100" style="font-size: 0.6rem;">TAMPILKAN</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-medizen mb-0">
                <thead>
                    <tr>
                        <th class="py-2 small">Patient Details</th>
                        <th class="py-2 small">No. Rawat</th>
                        <th class="py-2 small text-center">ID Encounter</th>
                        <th class="py-2 small text-center">ID Episode FHIR</th>
                        <th class="py-2 small text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $row)
                        <tr>
                            <td>
                                <div class="fw-bold text-slate-800 small">{{ strtoupper($row->nm_pasien) }}</div>
                                <div class="text-muted" style="font-size: 0.6rem;">RM: {{ $row->no_rkm_medis }}</div>
                            </td>
                            <td><div class="small fw-bold">{{ $row->no_rawat }}</div></td>
                            <td class="text-center small"><code>{{ $row->id_encounter ?: '-' }}</code></td>
                            <td class="text-center small"><code>{{ $row->id_episode ?: '-' }}</code></td>
                            <td class="pe-3 text-end"><span class="badge bg-light text-muted border">STUB</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5">NO DATA</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
