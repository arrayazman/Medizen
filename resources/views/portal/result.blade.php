<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kepada Pasien - Radiologi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
        }

        .header-bg {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 25px 0;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .expertise-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            text-align: justify;
        }

        .footer {
            text-align: center;
            font-size: 0.8rem;
            color: #888;
            margin-top: 40px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="header-bg mb-4">
        <div class="container text-center">
            <h4 class="fw-bold mb-1">Hasil Pemeriksaan Radiologi</h4>
            <p class="mb-0 opacity-75 small">Dokumen Elektronik Medis</p>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Info Pasien -->
                <div class="card card-custom p-3 bg-white">
                    <div class="row text-md-start text-center">
                        <div class="col-md-6 mb-2">
                            <small class="text-muted d-block">Nama Pasien</small>
                            <span class="fw-bold fs-5">{{ $order->patient->nama ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted d-block">Pemeriksaan</small>
                            <span class="fw-bold fs-5">{{ $order->examinationType->name ?? '-' }}
                                ({{ $order->modality }})</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Tanggal Periksa</small>
                            <span
                                class="fw-bold">{{ Carbon\Carbon::parse($order->scheduled_date)->translatedFormat('d F Y') }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <small class="text-muted d-block">Tanggal Selesai</small>
                            <span
                                class="fw-bold">{{ $order->result && $order->result->waktu_hasil ? Carbon\Carbon::parse($order->result->waktu_hasil)->translatedFormat('d F Y') : '-' }}</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Dokter Pembaca</small>
                            <span class="fw-bold">{{ $order->report->dokter->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Teks Hasil / Expertise -->
                <div class="card card-custom">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary"><i data-feather="file-text" style="width:18px;height:18px"
                                class="me-2"></i>Laporan Diagnostik (Expertise)</h6>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded expertise-text">
                            {!! nl2br(e($order->result->expertise ?? 'Belum ada hasil text.')) !!}
                        </div>
                    </div>
                </div>

                <!-- Gambar DICOM -->
                @if($PACSStudy)
                    <div class="card card-custom mt-3">
                        <div
                            class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-primary mb-0"><i data-feather="image" style="width:18px;height:18px"
                                    class="me-2"></i>Gambar Pemeriksaan</h6>
                            @php $tags = $PACSStudy['MainDicomTags'] ?? []; @endphp
                            <button type="button"
                                onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $tags['StudyInstanceUID'] ?? '' }}')"
                                class="btn btn-sm btn-outline-success border-0">
                                <i data-feather="eye" style="width:14px;height:14px"></i> Buka Viewer Terkait
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 justify-content-center">
                                @forelse($PACSStudy['SeriesData'] ?? [] as $sr)
                                    @php
                                        $srTags = $sr['MainDicomTags'] ?? [];
                                        $firstInst = $sr['_firstInstance'] ?? null;
                                    @endphp
                                    <div class="col-6 col-md-4">
                                        <div class="card shadow-sm mb-0">
                                            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center p-1"
                                                style="height: 120px;">
                                                @if($firstInst)
                                                    <a href="javascript:void(0)"
                                                        onclick="openImage('{{ route('pacs.instance-preview', $firstInst) }}')">
                                                        <img src="{{ route('pacs.instance-preview', $firstInst) }}"
                                                            class="img-fluid rounded"
                                                            style="max-height: 110px; object-fit: contain;">
                                                    </a>
                                                @else
                                                    <i data-feather="image" style="color:#666;width:24px;height:24px;"></i>
                                                @endif
                                            </div>
                                            <div class="card-body p-2 text-center" style="background:#f8f9fa;">
                                                <small class="d-block text-truncate mb-1"
                                                    style="font-size: 0.75rem;"><strong>{{ $srTags['SeriesDescription'] ?? 'Series' }}</strong></small>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted"><small>Belum ada gambar.</small></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-grid mt-4">
                    <a href="{{ route('orders.print', $order) }}" target="_blank"
                        class="btn btn-success py-3 rounded-pill fw-bold shadow-sm">
                        <i data-feather="download" style="width:18px;height:18px" class="me-2"></i>Unduh PDF Resmi
                    </a>
                </div>

            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Sistem Informasi Radiologi - Dokumen ini dapat dipertanggungjawabkan keasliannya
            selama link tidak diubah.
        </div>
    </div>

    {{-- Viewer Modals --}}
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" style="background:rgba(0,0,0,0.95)">
            <div class="modal-content" style="background:transparent;border:none">
                <div class="modal-header border-0" style="position:absolute;top:0;right:0;z-index:10">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0" data-bs-dismiss="modal"
                    style="cursor:zoom-out">
                    <img id="modalImage" src="" style="max-width:100%;max-height:100vh;object-fit:contain">
                </div>
            </div>
        </div>
    </div>

    <!-- Viewer Modal -->
    <div class="modal fade" id="viewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary py-2">
                    <h5 class="modal-title text-white">DICOM Viewer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="viewerIframe" src=""
                        style="width:100%; height:100%; border:none; background:#000;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();

        function openImage(url) {
            document.getElementById('modalImage').src = url;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        document.getElementById('imageModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalImage').src = '';
        });

        function openViewer(url) {
            document.getElementById('viewerIframe').src = url;
            new bootstrap.Modal(document.getElementById('viewerModal')).show();
        }

        document.getElementById('viewerModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('viewerIframe').src = '';
        });
    </script>
</body>

</html>