@extends('layouts.app')

@section('page-title', 'Detail Permintaan')

@section('content')
    <div class="container-fluid py-2">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('simrs.permintaan') }}" class="btn btn-sm btn-light rounded-0 me-2 px-2 py-1">
                <i data-feather="arrow-left" class="icon-xs"></i>
            </a>
            <h5 class="fw-bold mb-0">ORDER #{{ $simrsOrder->noorder }}</h5>
        </div>

        <div class="row g-2">
            <!-- Patient & Order Info -->
            <div class="col-lg-8">
                <!-- Info Utama Pasien -->
                <div class="bg-white p-3 rounded-0 border-0 mb-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar-md bg-dark text-white rounded-0 d-flex align-items-center justify-content-center fw-bold fs-4">
                                {{ substr($simrsOrder->nm_pasien, 0, 1) }}
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="fw-bold mb-0 text-dark">{{ strtoupper($simrsOrder->nm_pasien) }}</h4>
                            <div class="x-small text-muted fw-bold">
                                RM: {{ $simrsOrder->no_rkm_medis }} | {{ $simrsOrder->jk == 'L' ? 'LAKI-LAKI' : 'PEREMPUAN' }} | {{ \Carbon\Carbon::parse($simrsOrder->tgl_lahir)->format('d/m/Y') }} ({{ \Carbon\Carbon::parse($simrsOrder->tgl_lahir)->age }} TH)
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2 border-top pt-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="x-small fw-bold text-muted mb-0">NO. RAWAT</label>
                                <div class="small fw-bold">{{ $simrsOrder->no_rawat }}</div>
                            </div>
                            <div>
                                <label class="x-small fw-bold text-muted mb-0">ALAMAT</label>
                                <div class="x-small text-dark">{{ $simrsOrder->alamat }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="x-small fw-bold text-muted mb-0">DOKTER PERUJUK / UNIT</label>
                                <div class="small fw-bold text-primary">{{ $simrsOrder->nm_dokter }}</div>
                                <div class="x-small text-muted">{{ $simrsOrder->nm_poli }}</div>
                            </div>
                            <div>
                                <label class="x-small fw-bold text-muted mb-0">WAKTU ORDER SIMRS</label>
                                <div class="x-small fw-bold">{{ \Carbon\Carbon::parse($simrsOrder->tgl_permintaan)->format('d/m/Y') }} {{ $simrsOrder->jam_permintaan }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pemeriksaan & Klinis -->
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="bg-white p-3 rounded-0 border-0 h-100">
                            <label class="x-small fw-bold text-muted mb-2 d-block">DAFTAR PEMERIKSAAN</label>
                            <ul class="list-unstyled mb-0">
                                @foreach ($simrsOrder->items as $item)
                                    <li class="border-bottom py-2 d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-dark text-truncate" style="max-width: 200px;">{{ $item->nm_perawatan }}</span>
                                        <span class="x-small badge rounded-0 bg-light text-muted">{{ $item->kd_jenis_prw }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-white p-3 rounded-0 border-0 h-100">
                            <label class="x-small fw-bold text-muted mb-2 d-block">DIAGNOSA & INFO KLINIS</label>
                            <div class="p-2 bg-light x-small mb-2" style="min-height: 50px;">
                                <div class="fw-bold mb-1">DIAGNOSA:</div>
                                {{ $simrsOrder->diagnosa_klinis ?: '-' }}
                            </div>
                            <div class="p-2 bg-light x-small" style="min-height: 50px;">
                                <div class="fw-bold mb-1">INFO TAMBAHAN:</div>
                                {{ $simrsOrder->informasi_tambahan ?: '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PACS Images -->
                <div class="bg-white p-3 rounded-0 border-0 mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="x-small fw-bold text-muted mb-0">ARSIP GAMBAR (PACS)</label>
                        @if($PACSStudy)
                            <button onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $PACSStudy['MainDicomTags']['StudyInstanceUID'] ?? '' }}')"
                                class="btn btn-dark btn-sm rounded-0 x-small fw-bold py-0">OHIF VIEWER</button>
                        @endif
                    </div>
                    
                    @if($PACSStudy)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($PACSStudy['SeriesData'] ?? [] as $sr)
                                <div class="bg-light p-1 border" style="width: 100px; cursor: pointer;" onclick="openImage('{{ route('pacs.instance-preview', $sr['_firstInstance']) }}')">
                                    <img src="{{ route('pacs.instance-preview', $sr['_firstInstance']) }}" class="img-fluid bg-dark mb-1" style="height: 60px; width:100%; object-fit: contain;">
                                    <div class="x-small fw-bold text-truncate text-center" style="font-size: 0.6rem;">{{ $sr['MainDicomTags']['SeriesDescription'] ?? 'No Desc' }}</div>
                                    <div class="x-small text-center text-muted" style="font-size: 0.55rem;">{{ $sr['MainDicomTags']['Modality'] ?? '-' }} | {{ $sr['_instanceCount'] }} I</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center bg-light border-0">
                            <div class="x-small text-muted">TIDAK ADA GAMBAR DICOM TERDETEKSI</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar Actions -->
            <div class="col-lg-4">
                <div class="bg-white p-3 rounded-0 border-0 sticky-top" style="top: 10px;">
                    <label class="x-small fw-bold text-muted mb-3 d-block">AKSI INTEGRASI</label>
                    
                    <div class="border-start border-4 border-dark ps-3 mb-4">
                        <div class="x-small text-muted">STATUS RIS</div>
                        <div class="fw-bold mb-2">
                            @if($localOrder)
                                {!! $localOrder->status_badge !!}
                            @else
                                <span class="badge bg-secondary rounded-0 x-small">BELUM DI RIS</span>
                            @endif
                        </div>
                        <div class="x-small text-muted">SAMPEL SIMRS</div>
                        <div class="small fw-bold">{{ ($simrsOrder->tgl_sampel != '0000-00-00') ? $simrsOrder->tgl_sampel . ' ' . $simrsOrder->jam_sampel : 'BELUM' }}</div>
                    </div>

                    <div class="d-grid gap-1">
                        @if($simrsOrder->tgl_sampel == '0000-00-00')
                            <button class="btn btn-dark rounded-0 fw-bold py-2 x-small shadow-sm" onclick="takeSampleDetail('{{ $simrsOrder->noorder }}')">
                                CATAT SAMPEL & SINKRON
                            </button>
                        @endif

                        @if($localOrder)
                            <a href="{{ route('orders.show', $localOrder) }}" class="btn btn-light rounded-0 fw-bold py-2 x-small shadow-sm">
                                LIHAT DETAIL DI RIS
                            </a>
                        @endif

                        <a href="{{ route('simrs.hasil', ['search' => $simrsOrder->no_rawat]) }}" class="btn btn-success rounded-0 fw-bold py-2 x-small shadow-sm">
                            LIHAT HASIL RADIOLOGI
                        </a>
                    </div>

                    <div class="mt-4 p-2 bg-light x-small border-start border-info border-2">
                        <i data-feather="info" class="icon-xs me-1"></i>
                        @if($simrsOrder->tgl_sampel == '0000-00-00')
                            Klik tombol <b>CATAT SAMPEL</b> untuk kirim worklist ke PACS & simpan data ke RIS Medizen.
                        @else
                            Data ini sudah disinkronkan. Hasil bisa diproses di menu Order atau Hasil SIMRS.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal (Flat) -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark border-0 rounded-0">
                <div class="modal-header border-0 p-2" style="background:#000">
                    <h6 class="modal-title text-white small">IMAGE PREVIEW</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" onclick="document.querySelector('#imageModal .btn-close').click()">
                    <img id="modalImage" src="" style="max-width:100%; max-height:100%; object-fit:contain">
                </div>
            </div>
        </div>
    </div>

    <!-- Viewer Modal (Flat) -->
    <div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark border-0 rounded-0">
                <div class="modal-header border-0 p-2" style="background:#000">
                    <h6 class="modal-title text-white small">OHIF DICOM VIEWER</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="viewerIframe" src="" style="width:100%; height:100%; border:none; background:#000;"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openImage(url) {
            document.getElementById('modalImage').src = url;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
        function openViewer(url) {
            document.getElementById('viewerIframe').src = url;
            new bootstrap.Modal(document.getElementById('viewerModal')).show();
        }
        document.getElementById('imageModal').addEventListener('hidden.bs.modal', () => document.getElementById('modalImage').src = '');
        document.getElementById('viewerModal').addEventListener('hidden.bs.modal', () => document.getElementById('viewerIframe').src = '');

        function takeSampleDetail(noorder) {
            Swal.fire({
                title: 'CATAT SAMPEL?',
                text: "Sinkron ke PACS & SIMRS",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000',
                confirmButtonText: 'YA, PROSES',
                cancelButtonText: 'BATAL',
                customClass: { popup: 'rounded-0', confirmButton: 'rounded-0', cancelButton: 'rounded-0' }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("simrs.take-sample") }}', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json','X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: JSON.stringify({ noorder: noorder })
                    }).then(r => r.json()).then(res => {
                        if(res.success) window.location.reload();
                        else Swal.fire('GAGAL', res.message, 'error');
                    });
                }
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        .x-small { font-size: 0.7rem; }
        .avatar-md { width: 50px; height: 50px; }
        .bg-light { background-color: #f7f9fb !important; }
        .icon-xs { width: 14px; height: 14px; }
        .badge { font-size: 0.65rem; padding: 0.2rem 0.4rem; }
        .sticky-top { z-index: 10; }
        .btn-sm { font-size: 0.7rem; }
        .modal-content { box-shadow: none !important; }
    </style>
@endpush
