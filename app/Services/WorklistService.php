<?php

namespace App\Services;

use App\Models\RadiologyOrder;
use App\Models\WorklistLog;
use App\Helpers\DicomHelper;
use Illuminate\Support\Facades\Log;

class WorklistService
{
    protected PACSClient $client;

    public function __construct(PACSClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send a radiology order to PACS worklist.
     */
    public function sendToPACS(RadiologyOrder $order): bool
    {
        $order->load(['patient', 'referringDoctor', 'examinationType']);

        $worklistData = $this->buildWorklistPayload($order);

        Log::info('Sending worklist to PACS', [
            'order_id' => $order->id,
            'accession_number' => $order->accession_number,
        ]);

        $response = $this->client->post('/api/ris-worklist', $worklistData);

        // Log the transaction
        WorklistLog::create([
            'order_id' => $order->id,
            'status' => $response['success'] ? 'SUCCESS' : 'FAILED',
            'request_payload' => $worklistData,
            'response_payload' => $response['data'] ?? ['body' => $response['body']],
            'error_message' => $response['success'] ? null : ($response['body'] ?? 'Unknown error'),
        ]);

        if ($response['success']) {
            if ($order->status === RadiologyOrder::STATUS_ORDERED) {
                $order->update(['status' => RadiologyOrder::STATUS_SENT_TO_PACS]);
            }
            Log::info('Worklist sent successfully', ['order_id' => $order->id]);
            return true;
        }

        Log::error('Failed to send worklist to PACS', [
            'order_id' => $order->id,
            'response' => $response,
        ]);

        return false;
    }

    /**
     * Build DICOM worklist payload.
     */
    protected function buildWorklistPayload(RadiologyOrder $order): array
    {
        $patient = $order->patient;

        return [
            'AccessionNumber' => $order->accession_number,
            // Format name to DICOM standard (Last^First) if possible, but basic is fine
            'PatientName' => strtoupper($patient->nama ?? ''),
            'PatientID' => $patient->no_rm ?? '',
            'PatientBirthDate' => $patient->tgl_lahir
                ? DicomHelper::formatDicomDate($patient->tgl_lahir)
                : '',
            'PatientSex' => $patient->jenis_kelamin === 'L' ? 'M' : 'F',
            'StudyInstanceUID' => $order->study_instance_uid,
            'RequestedProcedureDescription' => strtoupper($order->procedure_description ?? ''),
            'Modality' => $order->modality,
            'ScheduledStationAETitle' => $order->station_ae_title ?? '',
            'ScheduledProcedureStepStartDate' => DicomHelper::formatDicomDate($order->scheduled_date),
            'ScheduledProcedureStepStartTime' => DicomHelper::formatDicomTime($order->scheduled_time),
            'ScheduledPerformingPhysicianName' => strtoupper($order->referringDoctor->name ?? ''),
            'ReferringPhysicianName' => strtoupper($order->referringDoctor->name ?? ''),
        ];
    }
}

