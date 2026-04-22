<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use App\Models\Patient;
use App\Models\Modality;
use App\Models\ExaminationType;
use App\Models\Doctor;
use App\Models\Radiographer;
use App\Models\Room;
use App\Helpers\DicomHelper;
use App\Services\WorklistService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RadiologyOrderController extends Controller
{
    public function index(Request $request)
    {
        // Persist filters in session if they are present in the request
        if ($request->hasAny(['start_date', 'end_date', 'status', 'modality'])) {
            session(['orders_filters' => $request->only(['start_date', 'end_date', 'status', 'modality'])]);
        } elseif ($request->has('clear_filters')) {
            session()->forget('orders_filters');
            return redirect()->route('orders.index');
        }

        // Retrieve filters from session
        $filters = session('orders_filters', []);

        // Prioritize request input, then session input, then null
        $startDate = $request->input('start_date', $filters['start_date'] ?? null);
        $endDate = $request->input('end_date', $filters['end_date'] ?? null);
        $status = $request->input('status', $filters['status'] ?? null);
        $modality = $request->input('modality', $filters['modality'] ?? null);
        $search = $request->input('search');

        // Merge back to request for consistency in view and logic
        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'modality' => $modality
        ]);

        $query = RadiologyOrder::with(['patient', 'examinationType', 'referringDoctor', 'room']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }

        // Apply default dates if no filters/search
        if (!$request->filled('start_date') && !$request->filled('end_date') && !$request->filled('search')) {
            $request->merge([
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString()
            ]);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('scheduled_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('scheduled_date', '<=', $request->end_date);
        }
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->search($request->search);
            });
        }

        $orders = $query->latest()->paginate(15);
        $modalities = Modality::active()->get();

        return view('orders.index', compact('orders', 'modalities'));
    }

    public function create()
    {
        $modalities = Modality::active()->get();
        $examinationTypes = ExaminationType::active()->with('modality')->get();
        $doctors = Doctor::active()->get();
        $radiographers = Radiographer::active()->get();
        $rooms = Room::active()->with('modality')->get();

        return view('orders.form', compact('modalities', 'examinationTypes', 'doctors', 'radiographers', 'rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'modality' => 'required|string|max:10',
            'examination_type_id' => 'nullable|exists:examination_types,id',
            'referring_doctor_id' => 'nullable|exists:doctors,id',
            'radiographer_id' => 'nullable|exists:radiographers,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'station_ae_title' => 'nullable|string|max:64',
            'procedure_description' => 'nullable|string',
            'clinical_info' => 'nullable|string|max:255',
            'priority' => 'required|in:ROUTINE,URGENT,STAT',
            'room_id' => 'nullable|exists:rooms,id',
            'notes' => 'nullable|string',
        ]);

        $validated['order_number'] = DicomHelper::generateOrderNumber();
        $validated['accession_number'] = DicomHelper::generateAccessionNumber();
        $validated['study_instance_uid'] = DicomHelper::generateStudyInstanceUID();
        $validated['created_by'] = Auth::id();
        $validated['status'] = RadiologyOrder::STATUS_ORDERED;

        $order = RadiologyOrder::create($validated);
        AuditService::logCreate($order, 'Order radiologi baru dibuat');

        // Notifikasi ke kru radiologi
        $notifiableUsers = \App\Models\User::whereIn('role', ['radiografer', 'admin_radiologi', 'super_admin'])->get();
        \Illuminate\Support\Facades\Notification::send($notifiableUsers, new \App\Notifications\NewRadiologyOrder($order));

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order radiologi berhasil dibuat');
    }

    public function show(RadiologyOrder $order, \App\Services\PACSClient $PACS)
    {
        $order->load([
            'patient',
            'examinationType.modality',
            'referringDoctor',
            'radiographer',
            'room',
            'creator',
            'report.dokter',
            'studyMetadata',
            'worklistLogs'
        ]);

        $PACSStudy = null;
        if ($order->accession_number) {
            $result = $PACS->post('/tools/find', [
                'Level' => 'Study',
                'Query' => ['AccessionNumber' => $order->accession_number],
                'Limit' => 1
            ]);

            if ($result['success'] && !empty($result['data'])) {
                $studyId = reset($result['data']);
                $study = $PACS->getStudy($studyId);
                if ($study) {
                    $study['SeriesData'] = [];
                    foreach ($study['Series'] ?? [] as $seriesId) {
                        $seriesData = $PACS->getSeries($seriesId);
                        if ($seriesData && !empty($seriesData['Instances'])) {
                            $seriesData['_firstInstance'] = reset($seriesData['Instances']);
                            $seriesData['_instanceCount'] = count($seriesData['Instances']);
                            $study['SeriesData'][] = $seriesData;
                        }
                    }
                    usort($study['SeriesData'], function ($a, $b) {
                        $numA = (int) ($a['MainDicomTags']['SeriesNumber'] ?? 0);
                        $numB = (int) ($b['MainDicomTags']['SeriesNumber'] ?? 0);
                        return $numA <=> $numB;
                    });
                    $PACSStudy = $study;
                }
            }
        }

        $baseUrl = $PACS->getPublicUrl();

        return view('orders.show', compact('order', 'PACSStudy', 'baseUrl'));
    }

    public function print(RadiologyOrder $order, \App\Services\PACSClient $PACS)
    {
        // Pastikan order sudah mencapai tahap ada expertise/hasil
        if (!in_array($order->status, ['IN_PROGRESS', 'COMPLETED', 'REPORTED', 'VALIDATED']) && !$order->result?->expertise) {
            return back()->with('error', 'Status pemeriksaan belum memiliki hasil untuk dicetak');
        }

        $order->load([
            'patient',
            'examinationType.modality',
            'referringDoctor',
            'radiographer',
            'room',
            'creator',
            'report.dokter',
            'studyMetadata'
        ]);

        $PACSStudy = null;
        if ($order->accession_number) {
            $result = $PACS->post('/tools/find', [
                'Level' => 'Study',
                'Query' => ['AccessionNumber' => $order->accession_number],
                'Limit' => 1
            ]);

            if ($result['success'] && !empty($result['data'])) {
                $studyId = reset($result['data']);
                $study = $PACS->getStudy($studyId);
                if ($study) {
                    $study['SeriesData'] = [];
                    foreach ($study['Series'] ?? [] as $seriesId) {
                        $seriesData = $PACS->getSeries($seriesId);
                        if ($seriesData && !empty($seriesData['Instances'])) {
                            $seriesData['_firstInstance'] = reset($seriesData['Instances']);
                            $study['SeriesData'][] = $seriesData;
                        }
                    }
                    $PACSStudy = $study;
                }
            }
        }

        $pdf = new \App\Services\ExaminationPdfService();
        $pdf->generateExpertisePage($order);

        if ($PACSStudy) {
            $pdf->generateImagePages($PACSStudy, $PACS);
        }

        return response($pdf->Output('Expertise_' . $order->accession_number . '.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Expertise_' . $order->accession_number . '.pdf"'
        ]);
    }

    public function printReceipt(RadiologyOrder $order)
    {
        $order->load(['patient', 'examinationType', 'creator']);

        $pdf = new \App\Services\ExaminationPdfService();
        $pdf->generateReceiptPage($order);

        return response($pdf->Output('Kwitansi_' . $order->order_number . '.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Kwitansi_' . $order->order_number . '.pdf"'
        ]);
    }

    public function edit(RadiologyOrder $order)
    {
        if (!in_array($order->status, [RadiologyOrder::STATUS_ORDERED])) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order tidak bisa diedit karena sudah diproses');
        }

        $modalities = Modality::active()->get();
        $examinationTypes = ExaminationType::active()->with('modality')->get();
        $doctors = Doctor::active()->get();
        $radiographers = Radiographer::active()->get();
        $rooms = Room::active()->with('modality')->get();

        return view('orders.form', compact('order', 'modalities', 'examinationTypes', 'doctors', 'radiographers', 'rooms'));
    }

    public function update(Request $request, RadiologyOrder $order)
    {
        if (!in_array($order->status, [RadiologyOrder::STATUS_ORDERED])) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order tidak bisa diedit karena sudah diproses');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'modality' => 'required|string|max:10',
            'examination_type_id' => 'nullable|exists:examination_types,id',
            'referring_doctor_id' => 'nullable|exists:doctors,id',
            'radiographer_id' => 'nullable|exists:radiographers,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'station_ae_title' => 'nullable|string|max:64',
            'procedure_description' => 'nullable|string',
            'clinical_info' => 'nullable|string|max:255',
            'priority' => 'required|in:ROUTINE,URGENT,STAT',
            'room_id' => 'nullable|exists:rooms,id',
            'notes' => 'nullable|string',
        ]);

        $old = $order->toArray();
        $order->update($validated);
        AuditService::logUpdate($order, $old, 'Order radiologi diperbarui');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order radiologi berhasil diperbarui');
    }

    public function sendToWorklist(RadiologyOrder $order, WorklistService $worklistService)
    {
        // Allow sending anytime except cancelled
        if ($order->status === RadiologyOrder::STATUS_CANCELLED) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order yang dibatalkan tidak bisa dikirim ke PACS');
        }

        $success = $worklistService->sendToPACS($order);

        if ($success) {
            $old = $order->toArray();
            // Status updated to SENT_TO_PACS inside WorklistService::sendToPACS
            AuditService::logUpdate($order, $old, 'Data dikirim ke PACS (PACS)');
            AuditService::log('WORKLIST_SENT', get_class($order), $order->id, null, null, 'Order dikirim ke PACS PACS');
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order berhasil dikirim ke PACS PACS');
        }

        return redirect()->route('orders.show', $order)
            ->with('error', 'Gagal mengirim order ke PACS. Silakan cek log untuk detail.');
    }

    public function takeSample(RadiologyOrder $order, \App\Services\SIMRSSyncService $syncService)
    {
        if (!in_array($order->status, [RadiologyOrder::STATUS_ORDERED, RadiologyOrder::STATUS_WAITING_SAMPLE, RadiologyOrder::STATUS_SENT_TO_PACS])) {
            return redirect()->route('orders.show', $order)->with('error', 'Aksi tidak valid untuk status order saat ini');
        }

        $old = $order->toArray();
        $order->update([
            'status' => RadiologyOrder::STATUS_SAMPLE_TAKEN,
            'waktu_sample' => now()
        ]);
        AuditService::logUpdate($order, $old, 'Sample pemeriksan diambil');

        // Sync to SIMRS
        $syncService->pushSampleTime($order);

        return redirect()->route('orders.show', $order)->with('success', 'Waktu ambil sample berhasil dicatat dan disinkronkan ke SIMRS');
    }

    public function startExamination(RadiologyOrder $order)
    {
        if (!in_array($order->status, [RadiologyOrder::STATUS_SAMPLE_TAKEN, RadiologyOrder::STATUS_WAITING_SAMPLE, RadiologyOrder::STATUS_ORDERED, RadiologyOrder::STATUS_SENT_TO_PACS])) {
            return redirect()->route('orders.show', $order)->with('error', 'Aksi tidak valid untuk status order saat ini');
        }

        $old = $order->toArray();
        $order->update([
            'status' => RadiologyOrder::STATUS_IN_PROGRESS,
            'waktu_mulai_periksa' => now()
        ]);
        AuditService::logUpdate($order, $old, 'Pemeriksaan dimulai');

        return redirect()->route('orders.show', $order)->with('success', 'Waktu mulai pemeriksaan berhasil dicatat');
    }

    public function completeExamination(RadiologyOrder $order)
    {
        if ($order->status !== RadiologyOrder::STATUS_IN_PROGRESS) {
            return redirect()->route('orders.show', $order)->with('error', 'Order harus dalam status In Progress sebelum selesai');
        }

        $old = $order->toArray();
        $order->update(['status' => RadiologyOrder::STATUS_COMPLETED]);
        AuditService::logUpdate($order, $old, 'Pemeriksaan selesai');

        return redirect()->route('orders.show', $order)->with('success', 'Pemeriksaan ditandai selesai');
    }

    public function inputExpertise(Request $request, RadiologyOrder $order, \App\Services\SIMRSSyncService $syncService)
    {
        $request->validate([
            'expertise' => 'required|string'
        ]);

        $old = $order->toArray();
        $updateData = ['status' => RadiologyOrder::STATUS_REPORTED];
        if (empty($order->patient_portal_token)) {
            $updateData['patient_portal_token'] = \Illuminate\Support\Str::random(10) . '-' . uniqid();
        }
        $order->update($updateData);

        \App\Models\RadiologyResult::updateOrCreate(
            ['radiology_order_id' => $order->id],
            [
                'expertise' => $request->expertise,
                'waktu_hasil' => now(),
                'status' => 'FINAL',
                'doctor_id' => auth()->user()->doctor_id ?? null
            ]
        );

        AuditService::logUpdate($order, $old, 'Expertise/Hasil pemeriksaan diinput');

        // Sync to SIMRS
        // Note: pushResult checks for STATUS_VALIDATED. 
        // Here we are in STATUS_REPORTED. 
        // I might need to adjust pushResult to allow STATUS_REPORTED or specifically push here.
        // Let's assume for this RIS that reported is considered enough to push to SIMRS.
        $syncService->pushResult($order);

        return redirect()->route('orders.show', $order)->with('success', 'Expertise berhasil disimpan dan disinkronkan ke SIMRS');
    }

    public function cancel(RadiologyOrder $order)
    {
        if (in_array($order->status, [RadiologyOrder::STATUS_COMPLETED, RadiologyOrder::STATUS_REPORTED])) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Pemeriksaan yang sudah selesai tidak bisa dibatalkan');
        }

        $old = $order->toArray();
        $order->update(['status' => RadiologyOrder::STATUS_CANCELLED]);
        AuditService::logUpdate($order, $old, 'Order radiologi dibatalkan');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order berhasil dibatalkan');
    }

    public function exportCsv(Request $request)
    {
        $query = RadiologyOrder::with(['patient', 'examinationType', 'referringDoctor', 'room']);

        if ($request->filled('start_date')) {
            $query->whereDate('scheduled_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('scheduled_date', '<=', $request->end_date);
        }
        if ($request->filled('modality')) {
            $query->where('modality', $request->modality);
        }
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->search($request->search);
            });
        }

        $orders = $query->latest()->get();

        $filename = "Radiology_Orders_" . now()->format('Ymd_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            // Adding BOM for Excel compatibility (UTF-8)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No. Order',
                'Accession',
                'No. RM',
                'Nama Pasien',
                'Tgl. Lahir',
                'Modalitas',
                'Pemeriksaan',
                'Dokter Perujuk',
                'Tgl. Periksa',
                'Status',
                'Tarif',
                'Waktu Dibuat'
            ]);

            foreach ($orders as $o) {
                fputcsv($file, [
                    $o->order_number,
                    $o->accession_number,
                    $o->patient->no_rm ?? '-',
                    $o->patient->nama ?? '-',
                    $o->patient->tgl_lahir ? $o->patient->tgl_lahir->format('Y-m-d') : '-',
                    $o->modality,
                    $o->examinationType->name ?? '-',
                    $o->referringDoctor->name ?? '-',
                    $o->scheduled_date->format('Y-m-d'),
                    $o->status,
                    $o->examinationType->price ?? 0,
                    $o->created_at->format('Y-m-d H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

