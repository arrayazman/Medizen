<?php

namespace App\Services;

use App\Models\RadiologyOrder;
use App\Models\StudyMetadata;
use Illuminate\Support\Facades\Log;

class StudySyncService
{
    protected PACSClient $client;

    public function __construct(PACSClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sync studies from PACS and match with orders.
     */
    public function syncStudies(): int
    {
        $studyIds = $this->client->getStudies();

        if ($studyIds === null) {
            Log::warning('StudySync: Unable to fetch studies from PACS');
            return 0;
        }

        $synced = 0;

        foreach ($studyIds as $studyId) {
            $studyData = $this->client->getStudy($studyId);

            if (!$studyData) {
                continue;
            }

            $mainTags = $studyData['MainDicomTags'] ?? [];
            $studyUid = $mainTags['StudyInstanceUID'] ?? null;

            if (!$studyUid) {
                continue;
            }

            // Find matching order
            $order = RadiologyOrder::where('study_instance_uid', $studyUid)
                ->whereIn('status', [
                    RadiologyOrder::STATUS_SENT_TO_PACS,
                    RadiologyOrder::STATUS_IN_PROGRESS,
                ])
                ->first();

            if (!$order) {
                continue;
            }

            // Count series and instances
            $seriesCount = count($studyData['Series'] ?? []);
            $instanceCount = 0;
            foreach ($studyData['Series'] ?? [] as $seriesId) {
                $seriesData = $this->client->get("/series/{$seriesId}");
                if ($seriesData) {
                    $instanceCount += count($seriesData['Instances'] ?? []);
                }
            }

            // Save or update metadata
            StudyMetadata::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'study_uid' => $studyUid,
                    'PACS_id' => $studyId,
                    'series_count' => $seriesCount,
                    'instance_count' => $instanceCount,
                    'study_date' => isset($mainTags['StudyDate'])
                        ? \Carbon\Carbon::createFromFormat('Ymd', $mainTags['StudyDate'])
                        : null,
                    'description' => $mainTags['StudyDescription'] ?? null,
                    'patient_name' => $mainTags['PatientName'] ?? null,
                    'raw_metadata' => $studyData,
                ]
            );

            // Update order status
            $order->update(['status' => RadiologyOrder::STATUS_COMPLETED]);
            $synced++;

            Log::info('StudySync: Matched and synced study', [
                'order_id' => $order->id,
                'study_uid' => $studyUid,
                'series_count' => $seriesCount,
                'instance_count' => $instanceCount,
            ]);
        }

        Log::info("StudySync: Completed. {$synced} studies synced.");
        return $synced;
    }
}

