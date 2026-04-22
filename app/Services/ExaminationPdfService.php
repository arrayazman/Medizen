<?php

namespace App\Services;

use TCPDF;
use App\Models\InstitutionSetting;

class ExaminationPdfService extends TCPDF
{
    protected $setting;

    // ─── Color Palette ────────────────────────────────────────────────────────
    private const COLOR_PRIMARY = [30, 30, 30];   // Near-black
    private const COLOR_ACCENT = [16, 115, 80];   // Medical green
    private const COLOR_LIGHT = [245, 247, 250];  // Light gray bg
    private const COLOR_BORDER = [210, 215, 220];  // Border gray
    private const COLOR_MUTED = [100, 110, 120];  // Muted text

    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setting = InstitutionSetting::first();

        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($this->setting?->hospital_name ?? 'RSU');
        $this->SetTitle('Hasil Pemeriksaan Radiologi');
        $this->SetSubject('Expertise Radiologi');

        $this->SetMargins(15, 58, 15);
        $this->SetHeaderMargin(3);
        $this->SetFooterMargin(18);
        $this->SetAutoPageBreak(true, 28);
    }

    // ─── Header ───────────────────────────────────────────────────────────────
    public function Header()
    {
        if (!$this->setting)
            return;

        $pageW = $this->getPageWidth();
        $headerH = 52;   // total header area height
        $barH = 7;    // bottom bar height
        $contentH = $headerH - $barH;

        // ── Full white background ──
        $this->SetFillColor(255, 255, 255);
        $this->Rect(0, 0, $pageW, $headerH, 'F');

        // ── Logo (vertically centered in content area) ──
        $logoW = 32;
        $logoX = 12;
        $logoY = ($contentH - 28) / 2; // center ~28mm tall logo
        $logoY = max(4, $logoY);

        if ($this->setting->logo_path && file_exists(public_path($this->setting->logo_path))) {
            $this->Image(
                public_path($this->setting->logo_path),
                (float) $logoX,
                (float) $logoY,
                (float) $logoW,
                0,
                '',
                '',
                'T',
                false,
                300
            );
        }

        // ── Vertical separator line ──
        $sepX = $logoX + $logoW + 5;
        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->SetLineWidth(0.4);
        $this->Line($sepX, 6, $sepX, $contentH - 4);

        // ── Hospital text block ──
        $textX = $sepX + 5;
        $textW = $pageW - $textX - 10;
        $textY = 6;

        // Hospital name — large, bold, dark
        $this->SetXY($textX, $textY);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->Cell($textW, 9, strtoupper($this->setting->hospital_name ?? ''), 0, 1, 'L');

        // Short colored rule under name
        $ruleY = $this->GetY();
        $this->SetFillColor(...self::COLOR_ACCENT);
        $this->Rect($textX, $ruleY, 28, 1, 'F');
        $this->SetFillColor(...self::COLOR_BORDER);
        $this->Rect($textX + 30, $ruleY, $textW - 30, 1, 'F');
        $this->Ln(3);

        // Address
        if ($this->setting->address) {
            $this->SetXY($textX, $ruleY + 3);
            $this->SetFont('helvetica', '', 8.5);
            $this->SetTextColor(...self::COLOR_MUTED);
            $this->Cell($textW, 5, $this->setting->address, 0, 1, 'L');
        }

        // Contact row
        $contact = array_filter([
            $this->setting->phone ? 'Telp: ' . $this->setting->phone : null,
            $this->setting->email ? $this->setting->email : null,
            $this->setting->website ? $this->setting->website : null,
        ]);
        if ($contact) {
            $this->SetX($textX);
            $this->SetFont('helvetica', '', 7.5);
            $this->SetTextColor(...self::COLOR_MUTED);
            $this->Cell($textW, 4.5, implode('   |   ', $contact), 0, 1, 'L');
        }

        // ── Bottom bar: accent green full width ──
        $barY = $headerH - $barH;
        $this->SetFillColor(...self::COLOR_ACCENT);
        $this->Rect(0, $barY, $pageW, $barH, 'F');

        // Bar left text
        $this->SetXY(12, $barY + 1.5);
        $this->SetFont('helvetica', 'B', 7.5);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(120, 4, 'SISTEM INFORMASI RADIOLOGI (RIS) & PACS', 0, 0, 'L');

        // Bar right: page number
        $this->SetXY(0, $barY + 1.5);
        $this->SetFont('helvetica', '', 7.5);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($pageW - 10, 4, 'Hal. ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');

        // Reset
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->SetLineWidth(0.2);
    }

    // ─── Footer ───────────────────────────────────────────────────────────────
    public function Footer()
    {
        if (!$this->setting)
            return;

        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();
        $fH = 16; // total footer height
        $fY = $pageH - $fH;

        // ── Footer image (if set) ──
        if ($this->setting->footer_path && file_exists(public_path($this->setting->footer_path))) {
            $this->Image(
                public_path($this->setting->footer_path),
                0,
                $fY,
                $pageW,
                $fH,
                '',
                '',
                'T',
                false,
                300
            );
            return;
        }

        // ── Fallback: designed footer bar ──

        // Top thin accent line
        $this->SetDrawColor(...self::COLOR_ACCENT);
        $this->SetLineWidth(0.6);
        $this->Line(0, $fY, $pageW, $fY);

        // Dark background bar
        $this->SetFillColor(...self::COLOR_PRIMARY);
        $this->Rect(0, $fY + 0.6, $pageW, $fH - 0.6, 'F');

        // Right accent strip
        $this->SetFillColor(...self::COLOR_ACCENT);
        $this->Rect($pageW - 5, $fY + 0.6, 5, $fH - 0.6, 'F');

        // Hospital name (left)
        $this->SetXY(8, $fY + 3);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(80, 4, strtoupper($this->setting->hospital_name ?? ''), 0, 0, 'L');

        // License code (center, muted)
        if ($this->setting->pacs_license_code) {
            $this->SetXY(0, $fY + 3);
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor(...self::COLOR_ACCENT);
            $this->Cell($pageW - 20, 4, 'License: ' . $this->setting->pacs_license_code, 0, 0, 'C');
        }

        // Divider line
        $this->SetDrawColor(60, 60, 60);
        $this->SetLineWidth(0.2);
        $this->Line(8, $fY + 8.5, $pageW - 13, $fY + 8.5);

        // Generated by (left bottom)
        $this->SetXY(8, $fY + 9.5);
        $this->SetFont('helvetica', 'I', 6.5);
        $this->SetTextColor(160, 160, 160);
        $this->Cell(80, 3.5, 'Dokumen ini digenerate secara otomatis oleh sistem', 0, 0, 'L');

        // Page number (right bottom)
        $this->SetXY(8, $fY + 9.5);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($pageW - 21, 3.5, 'Halaman ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');

        // Reset
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->SetLineWidth(0.2);
    }

    // ─── Expertise Page ───────────────────────────────────────────────────────
    public function generateExpertisePage($order)
    {
        $this->AddPage();
        $this->_renderWatermark();

        $pageW = $this->getPageWidth();

        // ── Section title ──
        $this->SetFillColor(...self::COLOR_PRIMARY);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell($pageW - 30, 7, '  HASIL PEMERIKSAAN RADIOLOGI', 0, 1, 'L', true);
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->Ln(4);

        // ── Patient info table ──
        $col1 = 38;   // label width
        $col2 = 3;    // colon
        $col3 = 55;   // value
        $gap = 6;    // gap between left and right columns

        $fields = [
            ['No. Rekam Medis', $order->patient->no_rm ?? '-', 'Accession No.', $order->accession_number],
            ['Nama Pasien', $order->patient->nama ?? '-', 'No. Order', $order->order_number],
            [
                'Jenis Kelamin',
                $order->patient->jenis_kelamin_label ?? '-',
                'Tgl. Pemeriksaan',
                $order->waktu_mulai_periksa
                ? \Carbon\Carbon::parse($order->waktu_mulai_periksa)->format('d/m/Y H:i')
                : $order->formatted_date,
            ],
            ['Umur', $order->patient->umur ?? '-', 'Dokter Pengirim', $order->referringDoctor->name ?? '-'],
            ['Pemeriksaan', ($order->examinationType->name ?? '-') . ' (' . $order->modality . ')', null, null],
        ];

        $this->SetFont('helvetica', '', 8.5);
        $rowBg = false;

        foreach ($fields as $row) {
            $yStart = $this->GetY();
            $xStart = 15;
            $rowH = 6;

            // Row background alternating
            if ($rowBg) {
                $this->SetFillColor(...self::COLOR_LIGHT);
                $this->Rect($xStart, $yStart, $pageW - 30, $rowH, 'F');
            }
            $rowBg = !$rowBg;

            // Left pair
            $this->SetXY($xStart, $yStart);
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(...self::COLOR_MUTED);
            $this->Cell($col1, $rowH, $row[0], 0, 0, 'L');
            $this->SetTextColor(...self::COLOR_PRIMARY);
            $this->Cell($col2, $rowH, ':', 0, 0, 'C');
            $this->SetFont('helvetica', 'B', 8.5);
            $this->Cell($col3, $rowH, $row[1], 0, 0, 'L');

            // Right pair (if exists)
            if (!empty($row[2])) {
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(...self::COLOR_MUTED);
                $this->Cell($col1, $rowH, $row[2], 0, 0, 'L');
                $this->SetTextColor(...self::COLOR_PRIMARY);
                $this->Cell($col2, $rowH, ':', 0, 0, 'C');
                $this->SetFont('helvetica', 'B', 8.5);
                $this->Cell(0, $rowH, $row[3], 0, 0, 'L');
            }

            $this->Ln($rowH);
        }

        // Divider
        $this->Ln(3);
        $this->SetDrawColor(...self::COLOR_ACCENT);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), $pageW - 15, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(...self::COLOR_BORDER);
        $this->Ln(6);

        // ── Expertise content ──
        $html = '
        <style>
            body  { font-family: helvetica; font-size: 10pt; color: #1e1e1e; }
            .greeting { font-size: 10pt; margin-bottom: 8px; }
            .content  { font-size: 10pt; line-height: 1.7; text-align: justify; }
        </style>
        <p class="greeting"><strong>Yth. Teman Sejawat,</strong></p>
        <div class="content">' . nl2br(htmlspecialchars($order->result?->expertise ?? '')) . '</div>';

        $this->writeHTML($html, true, false, true, false, '');
        $this->Ln(10);

        // ── Signature block ──
        $signDate = ($order->result && $order->result->waktu_hasil)
            ? \Carbon\Carbon::parse($order->result->waktu_hasil)->translatedFormat('d F Y')
            : \Carbon\Carbon::now()->translatedFormat('d F Y');

        $doctorName = ($order->result && $order->result->doctor)
            ? 'dr. ' . $order->result->doctor->name . ', Sp.Rad'
            : 'dr. Spesialis Radiologi, Sp.Rad';

        $signHtml = '
        <table width="100%" style="font-size: 9pt;">
            <tr>
                <td width="55%"></td>
                <td width="45%" align="center" style="color:#444;">
                    ' . ($this->setting->address ? explode(',', $this->setting->address)[0] : 'Kota') . ', ' . $signDate . '<br>
                    <strong>Dokter Spesialis Radiologi</strong>
                    <br><br><br><br><br>
                    <u><strong>' . $doctorName . '</strong></u><br>
                    <span style="color:#888; font-size:8.5pt;">SIP: 123.456.789</span>
                </td>
            </tr>
        </table>';

        $this->writeHTML($signHtml, true, false, true, false, '');
    }

    // ─── Image Pages ──────────────────────────────────────────────────────────
    public function generateImagePages($PACSStudy, $client)
    {
        if (empty($PACSStudy['SeriesData']))
            return;

        $maxW = 180; // Expanded to fill A4 with margins
        $maxH = 210; // Expanded to fill A4 with margins

        foreach ($PACSStudy['SeriesData'] as $sr) {
            $firstInst = $sr['_firstInstance'] ?? null;
            if (!$firstInst)
                continue;

            $imageContent = $client->getInstancePreview($firstInst);
            if (!$imageContent)
                continue;

            $imgRes = @imagecreatefromstring($imageContent);
            if ($imgRes === false)
                continue;

            $this->AddPage();
            $this->SetAutoPageBreak(false, 0);

            $origW = imagesx($imgRes);
            $origH = imagesy($imgRes);
            $ratio = $origW / $origH;

            // Resize based on maximized dimensions
            [$renderW, $renderH] = [$maxW, $maxW / $ratio];
            if ($renderH > $maxH) {
                $renderH = $maxH;
                $renderW = $maxH * $ratio;
            }

            $resized = imagecreatetruecolor((int) $origW, (int) $origH);
            imagefill($resized, 0, 0, imagecolorallocate($resized, 0, 0, 0));
            imagecopy($resized, $imgRes, 0, 0, 0, 0, $origW, $origH);

            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dicom_' . uniqid() . '.jpg';
            imagejpeg($resized, $tmpPath, 90);
            imagedestroy($imgRes);
            imagedestroy($resized);

            if (file_exists($tmpPath)) {
                $pageW = $this->getPageWidth();
                $pageH = $this->getPageHeight();

                $x = ($pageW - $renderW) / 2;
                $y = ($pageH - $renderH) / 2;

                // Ensure Y doesn't overlap header
                if ($y < 58)
                    $y = 58;

                $this->Image($tmpPath, (float) $x, (float) $y, (float) $renderW, (float) $renderH, 'JPEG');
                @unlink($tmpPath);
            }

            $this->SetAutoPageBreak(true, 28);
        }
    }

    // ─── Receipt Page ─────────────────────────────────────────────────────────
    public function generateReceiptPage($order)
    {
        $this->AddPage();

        $pageW = $this->getPageWidth();
        $price = $order->examinationType->price ?? 0;
        $date = $order->created_at->translatedFormat('d F Y H:i');

        // ── Title bar ──
        $this->SetFillColor(...self::COLOR_PRIMARY);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell($pageW - 30, 9, '  BUKTI PEMBAYARAN / KWITANSI', 0, 1, 'L', true);
        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->Ln(5);

        // ── Detail rows ──
        $items = [
            ['NOMOR ORDER', '#' . $order->order_number],
            ['NOMOR REKAM MEDIS', $order->patient->no_rm ?? '-'],
            ['NAMA PASIEN', $order->patient->nama ?? '-'],
            ['JENIS PEMERIKSAAN', ($order->examinationType->name ?? '-') . ' [' . $order->modality . ']'],
            ['TANGGAL TRANSAKSI', $date],
        ];

        $labelW = 60;
        $rowH = 7.5;
        $altBg = false;

        foreach ($items as $item) {
            $yNow = $this->GetY();
            if ($altBg) {
                $this->SetFillColor(...self::COLOR_LIGHT);
                $this->Rect(15, $yNow, $pageW - 30, $rowH, 'F');
            }
            $altBg = !$altBg;

            $this->SetXY(15, $yNow);
            $this->SetFont('helvetica', '', 8.5);
            $this->SetTextColor(...self::COLOR_MUTED);
            $this->Cell($labelW, $rowH, $item[0], 0, 0, 'L');

            $this->SetTextColor(...self::COLOR_PRIMARY);
            $this->Cell(5, $rowH, ':', 0, 0, 'C');
            $this->SetFont('helvetica', 'B', 8.5);
            $this->Cell(0, $rowH, $item[1], 0, 1, 'L');
        }

        $this->Ln(6);

        // ── Fee Breakdown ──
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(0, 7, 'RINCIAN BIAYA PEMERIKSAAN', 0, 1, 'L');
        $this->Ln(2);

        $exam = $order->examinationType;
        $breakdown = array_filter([
            'Jasa Rumah Sakit' => $exam->js_rs ?? 0,
            'Jasa Medis Dokter' => $exam->jm_dokter ?? 0,
            'Bahan Habis Pakai' => $exam->paket_bhp ?? 0,
            'Jasa Petugas/Kru' => $exam->jm_petugas ?? 0,
            'Manajemen/Admin' => $exam->manajemen ?? 0,
            'KSO Alat' => $exam->kso ?? 0,
        ]);

        if (empty($breakdown)) {
            $breakdown = ['Biaya Pemeriksaan' => $price];
        }

        $this->SetFont('helvetica', '', 8.5);
        $this->SetDrawColor(...self::COLOR_BORDER);
        foreach ($breakdown as $label => $val) {
            if ($val <= 0)
                continue;
            $this->Cell($pageW - 70, 6, $label, 'B', 0, 'L');
            $this->Cell(0, 6, 'Rp ' . number_format($val, 0, ',', '.'), 'B', 1, 'R');
        }

        $this->Ln(6);

        // ── Total box ──
        $boxH = 18;
        $yBox = $this->GetY();
        $this->SetFillColor(...self::COLOR_ACCENT);
        $this->Rect(15, $yBox, $pageW - 30, $boxH, 'F');

        $this->SetXY(20, $yBox + 3);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(80, 5, 'TOTAL PEMBAYARAN', 0, 0, 'L');

        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 5, 'Rp ' . number_format($price, 0, ',', '.'), 0, 0, 'R');

        $this->SetXY(20, $yBox + 10);
        $this->SetFont('helvetica', 'I', 7.5);
        $this->SetTextColor(220, 255, 240);
        $this->Cell(0, 5, 'Terbilang: ' . $this->_terbilang($price) . ' Rupiah', 0, 1, 'L');

        $this->SetTextColor(...self::COLOR_PRIMARY);
        $this->Ln(10);

        // ── Note ──
        $this->SetFont('helvetica', 'I', 7.5);
        $this->SetTextColor(...self::COLOR_MUTED);
        $this->Cell(0, 5, '* Kwitansi ini adalah bukti pembayaran yang sah dan dihasilkan secara otomatis oleh sistem.', 0, 1, 'L');
        $this->Ln(10);

        // ── Signature ──
        $signHtml = '
        <table width="100%" style="font-size: 9pt; color: #444;">
            <tr>
                <td width="55%"></td>
                <td width="45%" align="center">
                    Petugas Administrasi,<br><br><br><br><br>
                    <u><strong>' . (auth()->user()->name ?? 'Administrator') . '</strong></u><br>
                    
                </td>
            </tr>
        </table>';

        $this->writeHTML($signHtml, true, false, true, false, '');
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function _renderWatermark(): void
    {
        if (!$this->setting?->watermark_path)
            return;
        if (!file_exists(public_path($this->setting->watermark_path)))
            return;

        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->getAutoPageBreak();
        $this->SetAutoPageBreak(false, 0);
        $this->SetAlpha(0.07);

        $imgW = 110;
        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();
        $x = ($pageW - $imgW) / 2;
        $y = ($pageH - $imgW) / 2;

        $this->Image(public_path($this->setting->watermark_path), (float) $x, (float) $y, (float) $imgW, 0);

        $this->SetAlpha(1);
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->setPageMark();
    }

    /**
     * Convert number to Indonesian terbilang string.
     */
    private function _terbilang(int $number): string
    {
        $words = [
            '',
            'Satu',
            'Dua',
            'Tiga',
            'Empat',
            'Lima',
            'Enam',
            'Tujuh',
            'Delapan',
            'Sembilan',
            'Sepuluh',
            'Sebelas'
        ];

        if ($number < 12)
            return $words[$number];
        if ($number < 20)
            return $this->_terbilang($number - 10) . ' Belas';
        if ($number < 100)
            return $this->_terbilang((int) ($number / 10)) . ' Puluh ' . $this->_terbilang($number % 10);
        if ($number < 200)
            return 'Seratus ' . $this->_terbilang($number - 100);
        if ($number < 1000)
            return $this->_terbilang((int) ($number / 100)) . ' Ratus ' . $this->_terbilang($number % 100);
        if ($number < 2000)
            return 'Seribu ' . $this->_terbilang($number - 1000);
        if ($number < 1000000)
            return $this->_terbilang((int) ($number / 1000)) . ' Ribu ' . $this->_terbilang($number % 1000);
        if ($number < 1000000000)
            return $this->_terbilang((int) ($number / 1000000)) . ' Juta ' . $this->_terbilang($number % 1000000);
        return $this->_terbilang((int) ($number / 1000000000)) . ' Miliar ' . $this->_terbilang($number % 1000000000);
    }
}
