<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemeriksaan - {{ $order->order_number }}</title>
    <style>
        /* Base Styling */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        /* A4 Page Setup */
        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 1.5cm 1.5cm 2.5cm 1.5cm;
            /* Extra bottom padding for footer */
            margin: 1cm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
            box-sizing: border-box;
            background-image: url('{{ asset("img/watermark.png") }}');
            background-position: center;
            background-repeat: no-repeat;
            background-size: 60%;
        }

        /* Print Specifics */
        @media print {
            body {
                background: none;
                margin: 0;
            }

            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                /* Background watermark logic for printing */
                background-image: url('{{ asset("img/watermark.png") }}') !important;
                background-position: center !important;
                background-repeat: no-repeat !important;
                background-size: 60% !important;
                position: relative;
            }

            /* Hide UI elements */
            .no-print {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 0mm;
                /* Let the container handle margins */
            }
        }

        /* Letterhead Header */
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-logo {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }

        .header-text {
            flex-grow: 1;
            text-align: center;
        }

        .header-text h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-text p {
            margin: 3px 0 0 0;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Sub-Header Title */
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        /* Patient & Order Info Grid */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }

        .info-table td {
            vertical-align: top;
            padding: 4px;
        }

        .info-table td.label {
            width: 15%;
            font-weight: bold;
        }

        .info-table td.colon {
            width: 2%;
            text-align: center;
        }

        .info-table td.value {
            width: 33%;
        }

        /* Expertise Content */
        .expertise-container {
            font-size: 13px;
            line-height: 1.6;
            min-height: 300px;
        }

        .expertise-content {
            white-space: pre-wrap;
            text-align: justify;
        }

        /* Signature Area */
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            text-align: center;
            width: 300px;
            font-size: 12px;
        }

        .signature-space {
            height: 80px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Footer Banner */
        .footer-banner {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2cm;
            background-image: url('{{ asset("img/footer.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Action Buttons for Browser */
        .print-actions {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border-radius: 4px;
            border: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }

        /* Image Grid for Attachment Page */
        .dicom-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .dicom-item {
            width: 45%;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            background: #fff;
            page-break-inside: avoid;
        }

        .dicom-img {
            max-width: 100%;
            height: 250px;
            object-fit: contain;
            background-color: #000;
        }

        .dicom-desc {
            font-size: 11px;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Hasil</button>
        <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
    </div>

    <div class="page">
        {{-- Header / Kop Surat --}}
        <div class="header">
            <img src="{{ asset('img/rs-logo.png') }}" class="header-logo" alt="Logo RS">
            <div class="header-text">
                <h1>RUMAH SAKIT UMUM</h1>
                <p>Jl. Jendral Sudirman No. 123, Kota Metropolis, Kode Pos 12345</p>
                <p>Telp: (021) 1234567 | Email: info@rs-umum.com | Website: www.rs-umum.com</p>
                <p><strong>Sistem Informasi Radiologi (RIS) & PACS</strong></p>
            </div>
            <!-- Dummy block to balance logo width for centering -->
            <div style="width: 120px;"></div>
        </div>

        <div class="document-title">
            HASIL PEMERIKSAAN RADIOLOGI
        </div>

        {{-- Patient & Order Information --}}
        <table class="info-table">
            <tr>
                <td class="label">No. Rekam Medis</td>
                <td class="colon">:</td>
                <td class="value"><strong>{{ $order->patient->no_rm ?? '-' }}</strong></td>
                <td class="label">Accession No.</td>
                <td class="colon">:</td>
                <td class="value"><code>{{ $order->accession_number }}</code></td>
            </tr>
            <tr>
                <td class="label">Nama Pasien</td>
                <td class="colon">:</td>
                <td class="value"><strong>{{ $order->patient->nama ?? '-' }}</strong></td>
                <td class="label">No. Order</td>
                <td class="colon">:</td>
                <td class="value">{{ $order->order_number }}</td>
            </tr>
            <tr>
                <td class="label">Jenis Kelamin</td>
                <td class="colon">:</td>
                <td class="value">{{ $order->patient->jenis_kelamin_label ?? '-' }}</td>
                <td class="label">Tgl. Pemeriksaan</td>
                <td class="colon">:</td>
                <td class="value">
                    {{ $order->waktu_mulai_periksa ? \Carbon\Carbon::parse($order->waktu_mulai_periksa)->format('d/m/Y H:i') : $order->formatted_date }}
                </td>
            </tr>
            <tr>
                <td class="label">Umur</td>
                <td class="colon">:</td>
                <td class="value">{{ $order->patient->umur ?? '-' }}</td>
                <td class="label">Dokter Pengirim</td>
                <td class="colon">:</td>
                <td class="value">{{ $order->referringDoctor->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Pemeriksaan</td>
                <td class="colon">:</td>
                <td class="value" colspan="4"><strong>{{ $order->examinationType->name ?? '-' }}
                        ({{ $order->modality }})</strong></td>
            </tr>
            @if($order->clinical_info)
                <tr>
                    <td class="label">Ket. Klinis</td>
                    <td class="colon">:</td>
                    <td class="value" colspan="4">{{ $order->clinical_info }}</td>
                </tr>
            @endif
        </table>

        {{-- Expertise Content --}}
        <div class="expertise-container">
            <strong>Yth. Teman Sejawat,</strong><br><br>
            <div class="expertise-content">{{ $order->result?->expertise }}</div>
        </div>

        {{-- Signature Section --}}
        <div class="signature-section">
            <div class="signature-box">
                <div>Kota Metropolis,
                    {{ $order->result?->waktu_hasil ? \Carbon\Carbon::parse($order->result->waktu_hasil)->translatedFormat('d F Y') : \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                </div>
                <div>Dokter Spesialis Radiologi</div>
                <div class="signature-space"></div>
                <div class="signature-name">
                    @if($order->report && $order->report->dokter)
                        dr. {{ $order->report->dokter->name }}, Sp.Rad
                    @else
                        dr. Spesialis Radiologi, Sp.Rad
                    @endif
                </div>
                <div>SIP: 123.456.789</div>
            </div>
        </div>

        {{-- Footer Banner Image --}}
        <div class="footer-banner"></div>
    </div>

    {{-- Lampiran Gambar (Attachment Page) --}}
    @if(isset($PACSStudy['SeriesData']) && count($PACSStudy['SeriesData']) > 0)
        <div class="page">
            {{-- Header / Kop Surat --}}
            <div class="header">
                <img src="{{ asset('img/rs-logo.png') }}" class="header-logo" alt="Logo RS">
                <div class="header-text">
                    <h1>RUMAH SAKIT UMUM</h1>
                    <p>Jl. Jendral Sudirman No. 123, Kota Metropolis, Kode Pos 12345</p>
                    <p>Telp: (021) 1234567 | Email: info@rs-umum.com | Website: www.rs-umum.com</p>
                    <p><strong>Sistem Informasi Radiologi (RIS) & PACS</strong></p>
                </div>
                <div style="width: 120px;"></div>
            </div>

            <div class="document-title">
                LAMPIRAN GAMBAR PEMERIKSAAN
            </div>

            {{-- Quick Info --}}
            <table class="info-table" style="margin-bottom: 20px;">
                <tr>
                    <td class="label">Nama Pasien</td>
                    <td class="colon">:</td>
                    <td class="value"><strong>{{ $order->patient->nama ?? '-' }}</strong></td>
                    <td class="label">Accession No.</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $order->accession_number }}</td>
                </tr>
                <tr>
                    <td class="label">No. Rekam Medis</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $order->patient->no_rm ?? '-' }}</td>
                    <td class="label">Tgl. Pemeriksaan</td>
                    <td class="colon">:</td>
                    <td class="value">
                        {{ $order->waktu_mulai_periksa ? \Carbon\Carbon::parse($order->waktu_mulai_periksa)->format('d/m/Y H:i') : $order->formatted_date }}
                    </td>
                </tr>
            </table>

            {{-- DICOM Images Grid --}}
            <div class="dicom-grid">
                @foreach($PACSStudy['SeriesData'] as $sr)
                    @php
                        $srTags = $sr['MainDicomTags'] ?? [];
                        $firstInst = $sr['_firstInstance'] ?? null;
                    @endphp
                    @if($firstInst)
                        <div class="dicom-item">
                            <img src="{{ route('pacs.instance-preview', $firstInst) }}" class="dicom-img" alt="DICOM Image">
                            <div class="dicom-desc">{{ $srTags['SeriesDescription'] ?? 'No Description' }}
                                ({{ $srTags['Modality'] ?? '-' }})</div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Footer Banner Image --}}
            <div class="footer-banner"></div>
        </div>
    @endif

</body>

</html>

