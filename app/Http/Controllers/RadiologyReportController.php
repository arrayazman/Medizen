<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use App\Models\RadiologyReport;
use App\Models\Doctor;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class RadiologyReportController extends Controller
{
    public function create(RadiologyOrder $order)
    {
        if ($order->report) {
            return redirect()->route('reports.edit', $order->report);
        }

        $doctors = Doctor::active()->get();
        return view('reports.form', compact('order', 'doctors'));
    }

    public function store(Request $request, RadiologyOrder $order)
    {
        $validated = $request->validate([
            'hasil' => 'required|string',
            'kesimpulan' => 'required|string',
            'dokter_id' => 'required|exists:doctors,id',
        ]);

        $validated['order_id'] = $order->id;
        $validated['status'] = 'DRAFT';

        $report = RadiologyReport::create($validated);
        $order->update(['status' => RadiologyOrder::STATUS_REPORTED]);
        AuditService::logCreate($report, 'Laporan radiologi dibuat');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Laporan radiologi berhasil disimpan');
    }

    public function edit(RadiologyReport $report)
    {
        $report->load('order.patient');
        $doctors = Doctor::active()->get();
        return view('reports.form', compact('report', 'doctors'));
    }

    public function update(Request $request, RadiologyReport $report)
    {
        if ($report->isValidated()) {
            return redirect()->route('orders.show', $report->order)
                ->with('error', 'Laporan yang sudah divalidasi tidak bisa diedit');
        }

        $validated = $request->validate([
            'hasil' => 'required|string',
            'kesimpulan' => 'required|string',
            'dokter_id' => 'required|exists:doctors,id',
        ]);

        $old = $report->toArray();
        $report->update($validated);
        AuditService::logUpdate($report, $old, 'Laporan radiologi diperbarui');

        return redirect()->route('orders.show', $report->order)
            ->with('success', 'Laporan berhasil diperbarui');
    }

    public function validate_report(RadiologyReport $report, \App\Services\SIMRSSyncService $syncService)
    {
        if ($report->isValidated()) {
            return redirect()->route('orders.show', $report->order)
                ->with('error', 'Laporan sudah divalidasi');
        }

        $old = $report->toArray();
        $report->update([
            'status' => 'VALIDATED',
            'validated_at' => now(),
        ]);

        $report->order->update(['status' => RadiologyOrder::STATUS_VALIDATED]);
        AuditService::logUpdate($report, $old, 'Laporan radiologi divalidasi');

        // Sync to SIMRS
        $syncSuccess = $syncService->pushResult($report->order);

        if ($syncSuccess) {
            return redirect()->route('orders.show', $report->order)
                ->with('success', 'Laporan berhasil divalidasi dan dikirim ke SIMRS');
        } else {
            return redirect()->route('orders.show', $report->order)
                ->with('success', 'Laporan berhasil divalidasi locally')
                ->with('warning', 'Gagal mengirim data ke SIMRS, pastikan koneksi database SIMRS benar.');
        }
    }

    public function pdf(RadiologyReport $report)
    {
        $report->load('order.patient', 'order.examinationType', 'dokter');

        $pdf = PDF::loadView('reports.pdf', compact('report'));
        $pdf->setPaper('A4');

        return $pdf->stream('laporan-radiologi-' . $report->order->accession_number . '.pdf');
    }
}
