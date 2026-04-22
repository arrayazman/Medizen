<div class="row g-2">
    <!-- Patient & Order Info -->
    <div class="col-lg-8">
        <!-- Info Utama Pasien -->
        <div class="bg-white p-3 rounded-0 border mb-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-md bg-dark text-white rounded-0 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px;">
                        {{ substr($simrsOrder->nm_pasien, 0, 1) }}
                    </div>
                </div>
                <div class="col">
                    <h4 class="fw-bold mb-0 text-dark">{{ strtoupper($simrsOrder->nm_pasien) }}</h4>
                    <div class="x-small text-muted fw-bold" style="font-size: 0.7rem;">
                        RM: <span class="privacy-mask">{{ $simrsOrder->no_rkm_medis }}</span> | {{ $simrsOrder->jk == 'L' ? 'LAKI-LAKI' : 'PEREMPUAN' }} | <span class="privacy-mask">{{ \Carbon\Carbon::parse($simrsOrder->tgl_lahir)->format('d/m/Y') }} ({{ \Carbon\Carbon::parse($simrsOrder->tgl_lahir)->age }} TH)</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2 border-top pt-3">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-0" style="font-size: 0.65rem;">NO. RAWAT</label>
                        <div class="small fw-bold">{{ $simrsOrder->no_rawat }}</div>
                    </div>
                    <div>
                        <label class="x-small fw-bold text-muted mb-0" style="font-size: 0.65rem;">ALAMAT</label>
                        <div class="x-small text-dark" style="font-size: 0.7rem;"><span class="privacy-mask">{{ $simrsOrder->alamat }}</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="x-small fw-bold text-muted mb-0" style="font-size: 0.65rem;">DOKTER PERUJUK / UNIT</label>
                        <div class="small fw-bold text-primary"><span class="privacy-mask">{{ $simrsOrder->nm_dokter }}</span></div>
                        <div class="x-small text-muted" style="font-size: 0.7rem;">{{ $simrsOrder->nm_poli }}</div>
                    </div>
                    <div>
                        <label class="x-small fw-bold text-muted mb-0" style="font-size: 0.65rem;">WAKTU ORDER SIMRS</label>
                        <div class="x-small fw-bold" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($simrsOrder->tgl_permintaan)->format('d/m/Y') }} {{ $simrsOrder->jam_permintaan }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pemeriksaan & Klinis -->
        <div class="row g-2">
            <div class="col-md-6">
                <div class="bg-white p-3 rounded-0 border h-100">
                    <label class="x-small fw-bold text-muted mb-2 d-block" style="font-size: 0.65rem;">DAFTAR PEMERIKSAAN</label>
                    <ul class="list-unstyled mb-0">
                        @foreach ($simrsOrder->items as $item)
                            <li class="border-bottom py-2 d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-dark text-truncate" style="max-width: 200px;"><span class="privacy-mask">{{ $item->nm_perawatan }}</span></span>
                                <span class="x-small badge rounded-0 bg-light text-muted border" style="font-size: 0.6rem;">{{ $item->kd_jenis_prw }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-white p-3 rounded-0 border h-100">
                    <label class="x-small fw-bold text-muted mb-2 d-block" style="font-size: 0.65rem;">DIAGNOSA & INFO KLINIS</label>
                    <div class="p-2 bg-light x-small mb-2" style="min-height: 50px; font-size: 0.7rem;">
                        <div class="fw-bold mb-1">DIAGNOSA:</div>
                        <span class="privacy-mask">{{ $simrsOrder->diagnosa_klinis ?: '-' }}</span>
                    </div>
                    <div class="p-2 bg-light x-small" style="min-height: 50px; font-size: 0.7rem;">
                        <div class="fw-bold mb-1">INFO TAMBAHAN:</div>
                        <span class="privacy-mask">{{ $simrsOrder->informasi_tambahan ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PACS Images -->
        <div class="bg-white p-3 rounded-0 border mt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="x-small fw-bold text-muted mb-0" style="font-size: 0.65rem;">ARSIP GAMBAR (PACS)</label>
                <div class="d-flex gap-1">
                    @if($PACSStudy)
                        @if(($PACSStudy['MainDicomTags']['AccessionNumber'] ?? '') !== $simrsOrder->noorder)
                            <button onclick="updatePACSAccession('{{ $simrsOrder->noorder }}')"
                                class="btn btn-warning btn-sm rounded-0 x-small fw-bold py-0" style="font-size: 0.6rem;">SINKRON ACC NO</button>
                        @endif
                        <button onclick="openViewer('{{ $baseUrl }}/ohif/viewer?StudyInstanceUIDs={{ $PACSStudy['MainDicomTags']['StudyInstanceUID'] ?? '' }}')"
                            class="btn btn-dark btn-sm rounded-0 x-small fw-bold py-0" style="font-size: 0.6rem;">OHIF VIEWER</button>
                    @endif
                    <a href="{{ route('pacs.upload') }}?accession={{ $simrsOrder->noorder }}"
                        target="_blank"
                        class="btn btn-emerald btn-sm rounded-0 x-small fw-bold py-0"
                        style="font-size: 0.6rem;">
                        <i data-feather="upload-cloud" style="width: 10px;"></i> UPLOAD DICOM
                    </a>
                </div>
            </div>

            @if($PACSStudy)
                <div class="d-flex flex-wrap gap-2">
                    @foreach($PACSStudy['SeriesData'] ?? [] as $sr)
                        <div class="bg-light p-1 border" style="width: 100px; cursor: pointer;" onclick="openImageDetail('{{ route('pacs.instance-preview', $sr['_firstInstance']) }}')">
                            <img src="{{ route('pacs.instance-preview', $sr['_firstInstance']) }}" class="img-fluid bg-dark mb-1" style="height: 60px; width:100%; object-fit: contain;">
                            <div class="x-small fw-bold text-truncate text-center" style="font-size: 0.6rem;">{{ $sr['MainDicomTags']['SeriesDescription'] ?? 'No Desc' }}</div>
                            <div class="x-small text-center text-muted" style="font-size: 0.55rem;">{{ $sr['MainDicomTags']['Modality'] ?? '-' }} | {{ $sr['_instanceCount'] }} I</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center bg-light border">
                    <div class="x-small text-muted" style="font-size: 0.7rem;">TIDAK ADA GAMBAR DICOM TERDETEKSI</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Sidebar Actions -->
    <div class="col-lg-4">
        <div class="bg-white p-3 rounded-0 border sticky-top" style="top: 10px;">
            <label class="x-small fw-bold text-muted mb-3 d-block" style="font-size: 0.65rem;">ALUR KERJA & WORKLIST</label>
            
            <div class="border-start border-4 border-dark ps-3 mb-4">
                <div class="x-small text-muted" style="font-size: 0.65rem;">STATUS PEMERIKSAAN</div>
                <div class="fw-bold mb-2">
                    @if($localOrder)
                        {!! $localOrder->status_badge !!}
                    @else
                        <span class="badge bg-secondary rounded-0 x-small" style="font-size: 0.65rem;">BELUM TERDAFTAR</span>
                    @endif
                </div>
                <div class="x-small text-muted" style="font-size: 0.65rem;">SAMPEL SIMRS</div>
                <div class="small fw-bold">{{ ($simrsOrder->tgl_sampel != '0000-00-00') ? $simrsOrder->tgl_sampel . ' ' . $simrsOrder->jam_sampel : 'BELUM' }}</div>
            </div>

            <div class="d-grid gap-1">
                @if(!$localOrder || $localOrder->status === 'PENDING')
                    <div class="mb-2 p-2 bg-light border rounded-0">
                        <label class="x-small fw-bold text-muted mb-1 d-block" style="font-size: 0.6rem;">TARGET MODALITY (WORKLIST)</label>
                        <select class="form-select form-select-sm rounded-0 x-small mb-2" id="targetModalityInternal">
                            @foreach($pacsModalities as $mod)
                                <option value="{{ is_array($mod) ? $mod['Name'] : $mod }}">{{ is_array($mod) ? $mod['Name'] : strtoupper($mod) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-dark w-100 rounded-0 fw-bold py-2 x-small shadow-sm" style="font-size: 0.7rem;" onclick="takeSampleDetail('{{ $simrsOrder->noorder }}', $('#targetModalityInternal').val())">
                            CATAT SAMPEL & KIRIM WORKLIST
                        </button>
                    </div>
                @else
                    <!-- Workflow Buttons -->
                    <div class="mb-2 d-grid gap-1">
                        @if($localOrder->status === 'SAMPLE_TAKEN')
                            <button class="btn btn-primary rounded-0 fw-bold py-2 x-small shadow-sm" style="font-size: 0.7rem;" onclick="updateLocalStatus('{{ $localOrder->id }}', 'EXAMINING')">
                                MULAI PEMERIKSAAN
                            </button>
                        @elseif($localOrder->status === 'EXAMINING')
                            <button class="btn btn-info rounded-0 fw-bold py-2 x-small shadow-sm text-white" style="font-size: 0.7rem;" onclick="updateLocalStatus('{{ $localOrder->id }}', 'COMPLETED')">
                                SELESAI PEMERIKSAAN
                            </button>
                        @endif
                        
                        <div class="p-2 border bg-light mb-1">
                            <label class="x-small fw-bold text-muted mb-1 d-block" style="font-size: 0.55rem;">MODALITY UNTUK KIRIM ULANG</label>
                            <select class="form-select form-select-sm rounded-0 x-small mb-2" id="targetModalityInternalResend">
                                @foreach($pacsModalities as $mod)
                                    <option value="{{ is_array($mod) ? $mod['Name'] : $mod }}">{{ is_array($mod) ? $mod['Name'] : strtoupper($mod) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-dark w-100 rounded-0 fw-bold py-1 x-small shadow-sm" style="font-size: 0.65rem;" onclick="sendWorklistDetail('{{ $localOrder->id }}', $('#targetModalityInternalResend').val())">
                                KIRIM ULANG WORKLIST
                            </button>
                        </div>
                    </div>
                @endif

                @if($localOrder)
                    <a href="{{ route('orders.show', $localOrder) }}" class="btn btn-light rounded-0 border fw-bold py-2 x-small shadow-sm" style="font-size: 0.7rem;">
                        <i data-feather="monitor" style="width: 12px; height: 12px;" class="me-1"></i> LIHAT DETAIL DI RIS
                    </a>
                @endif

                <a href="{{ route('simrs.hasil', ['search' => $simrsOrder->no_rawat]) }}" 
                    target="_blank"
                    class="btn btn-emerald-soft text-emerald border border-emerald rounded-0 fw-bold py-2 x-small shadow-sm" 
                    style="font-size: 0.7rem; border-color: rgba(16, 185, 129, 0.4) !important;">
                    <i data-feather="external-link" style="width: 12px; height: 12px;" class="me-1"></i>
                    LIHAT RIWAYAT HASIL SIMRS
                </a>
            </div>

            @if($simrsOrder->has_expertise)
                <div class="mt-4 p-3 bg-light border-start border-success border-4">
                    <label class="x-small fw-bold text-success mb-1 d-block" style="font-size: 0.65rem;">HASIL EXPERTISE (SIMRS/RIS)</label>
                    <div class="x-small text-dark fw-bold privacy-mask" style="font-size: 0.7rem; white-space: pre-line; max-height: 200px; overflow-y: auto;">
                        {{ $simrsOrder->expertise_content }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
