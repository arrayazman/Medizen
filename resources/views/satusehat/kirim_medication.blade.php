@extends('layouts.app')

@section('content')
<div class="row m-0 p-0">
    <div class="col-12 p-0">
        <div class="card card-medizen rounded-0 border-0 shadow-none">
            <div class="card-header bg-white border-bottom rounded-0 py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold border-start border-4 border-emerald-500 ps-2">SatuSehat Kemenkes: Medication Dispense</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-emerald-600 btn-sm rounded-0" id="btn-batch-send">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Terpilih
                        </button>
                        <button class="btn btn-outline-secondary btn-sm rounded-0" onclick="window.location.reload()">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Filter Section -->
                <div class="bg-slate-50 p-2 border-bottom">
                    <form action="{{ route('satusehat.kirim-medication') }}" method="GET" class="row g-2">
                        <div class="col-md-4 d-flex gap-1">
                            <input type="date" name="tgl1" value="{{ $tgl1 }}" class="form-control form-control-sm rounded-0 border-slate-300">
                            <span class="align-self-center text-slate-500">s/d</span>
                            <input type="date" name="tgl2" value="{{ $tgl2 }}" class="form-control form-control-sm rounded-0 border-slate-300">
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <input type="text" name="keyword" value="{{ $keyword }}" placeholder="Cari No.Rawat / Nama / Obat..." class="form-control rounded-0 border-slate-300 shadow-none">
                                <button class="btn btn-emerald-600 rounded-0" type="submit"><i class="fas fa-search"></i> Search</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table Section -->
                <div class="table-responsive">
                    <table class="table table-hover table-medizen m-0">
                        <thead>
                            <tr>
                                <th width="30" class="text-center bg-slate-100"><input type="checkbox" id="check-all" class="form-check-input"></th>
                                <th class="bg-slate-100">Status</th>
                                <th class="bg-slate-100">Pasien</th>
                                <th class="bg-slate-100">No. Rawat</th>
                                <th class="bg-slate-100">Dokter & No. Resep</th>
                                <th class="bg-slate-100">Obat & Aturan Pakai</th>
                                <th class="bg-slate-100">Tgl. Beri</th>
                                <th class="bg-slate-100">ID Medication Dispense</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $row)
                            <tr class="align-middle border-bottom">
                                <td class="text-center">
                                    @if(!$row->id_medication_dispense)
                                    <input type="checkbox" class="form-check-input check-item" value="{{ $row->no_rawat }}|{{ $row->kode_brng }}|{{ $row->no_resep }}">
                                    @else
                                    <i class="fas fa-check-circle text-emerald-500"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge @if($row->status_lanjut == 'Ralan') bg-primary @else bg-success @endif rounded-pill px-2" style="font-size: 10px;">
                                        {{ $row->status_lanjut }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $row->nm_pasien }}</div>
                                    <div class="text-slate-500" style="font-size: 11px;">{{ $row->no_rkm_medis }} / NIK: {{ $row->no_ktp_pasien ?: '-' }}</div>
                                </td>
                                <td><span class="text-emerald-700 fw-medium">{{ $row->no_rawat }}</span></td>
                                <td>
                                    <div class="text-slate-700">{{ $row->nama_dokter }}</div>
                                    <div class="text-slate-500" style="font-size: 11px;">Resep: {{ $row->no_resep }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $row->obat_display }}</div>
                                    <div class="text-slate-500 italic" style="font-size: 11px;">{{ $row->aturan }} ({{ $row->jml }} {{ $row->denominator_code }})</div>
                                </td>
                                <td>
                                    <div class="text-slate-700" style="font-size: 11px;">{{ $row->tgl_perawatan }}</div>
                                    <div class="text-slate-500" style="font-size: 11px;">{{ $row->jam_beri }}</div>
                                </td>
                                <td>
                                    @if($row->id_medication_dispense)
                                    <code class="text-emerald-500 fw-bold">{{ $row->id_medication_dispense }}</code>
                                    @else
                                    <span class="text-slate-400 small italic">Belum terkirim</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 bg-slate-50 text-slate-400 italic">Data pemberian obat tidak ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div class="p-2 border-top d-flex justify-content-between align-items-center bg-white">
                    <div class="small text-slate-500">
                        Showing {{ $orders->firstItem() ?: 0 }} to {{ $orders->lastItem() ?: 0 }} of {{ $orders->total() }} entries
                    </div>
                    <div>
                        {{ $orders->appends(request()->input())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-medizen { font-size: 12px; }
    .table-medizen thead th { font-weight: 600; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
    .table-medizen tbody tr:hover { background-color: #f1f5f9; }
    .btn-emerald-600 { background-color: #059669; color: white; border: none; }
    .btn-emerald-600:hover { background-color: #047857; color: white; }
    .border-emerald-500 { border-color: #10b981 !important; }
</style>

<script>
document.getElementById('check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.check-item').forEach(el => el.checked = this.checked);
});

document.getElementById('btn-batch-send')?.addEventListener('click', function() {
    const selected = Array.from(document.querySelectorAll('.check-item:checked')).map(el => el.value);
    if (selected.length === 0) {
        alert('Pilih data obat yang akan dikirim terlebih dahulu.');
        return;
    }
    
    if (confirm(`Kirim ${selected.length} data medication dispense ke SatuSehat?`)) {
        alert('Fitur pengiriman batch sedang disiapkan.');
    }
});
</script>
@endsection
