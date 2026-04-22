<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Radiologi - {{ $report->order->accession_number }}</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 3px double #0f9d58; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { color: #0f9d58; margin: 0; font-size: 16pt; }
        .header h3 { margin: 5px 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 9pt; color: #666; }
        .patient-info { display: flex; gap: 40px; margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px; }
        .patient-info table { font-size: 10pt; }
        .patient-info td { padding: 2px 10px 2px 0; }
        .section { margin: 15px 0; }
        .section h4 { color: #0f9d58; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 8px; }
        .section .content { padding: 5px 10px; line-height: 1.6; }
        .footer { margin-top: 30px; text-align: right; }
        .footer .signature { margin-top: 60px; }
        .footer .doctor-name { font-weight: bold; border-top: 1px solid #333; display: inline-block; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>RUMAH SAKIT ISLAM</h2>
        <h3>INSTALASI RADIOLOGI</h3>
        <p>Jl. Contoh No. 123, Kota Contoh | Telp: (021) 1234567</p>
    </div>

    <h3 style="text-align:center; margin-bottom:5px;">HASIL PEMERIKSAAN RADIOLOGI</h3>

    <div style="display:flex; gap:40px; margin-bottom:15px;">
        <table style="font-size:10pt;">
            <tr><td><strong>No. RM</strong></td><td>: {{ $report->order->patient->no_rm ?? '-' }}</td></tr>
            <tr><td><strong>Nama</strong></td><td>: {{ $report->order->patient->nama ?? '-' }}</td></tr>
            <tr><td><strong>JK / Umur</strong></td><td>: {{ $report->order->patient->jenis_kelamin_label ?? '-' }} / {{ $report->order->patient->umur ?? '-' }}</td></tr>
        </table>
        <table style="font-size:10pt;">
            <tr><td><strong>Accession No</strong></td><td>: {{ $report->order->accession_number }}</td></tr>
            <tr><td><strong>Modalitas</strong></td><td>: {{ $report->order->modality }}</td></tr>
            <tr><td><strong>Tanggal Periksa</strong></td><td>: {{ $report->order->formatted_date }}</td></tr>
            <tr><td><strong>Pemeriksaan</strong></td><td>: {{ $report->order->examinationType->name ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h4>HASIL PEMERIKSAAN</h4>
        <div class="content">{!! nl2br(e($report->hasil)) !!}</div>
    </div>

    <div class="section">
        <h4>KESIMPULAN</h4>
        <div class="content"><strong>{!! nl2br(e($report->kesimpulan)) !!}</strong></div>
    </div>

    <div class="footer">
        <p>{{ now()->format('d F Y') }}</p>
        <p>Dokter Radiologi,</p>
        <div class="signature">
            <div class="doctor-name">{{ $report->dokter->name ?? '-' }}</div>
        </div>
        @if($report->validated_at)
        <p style="font-size:9pt; color:#0f9d58; margin-top:5px;">✓ Divalidasi pada {{ $report->validated_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>
</body>
</html>
