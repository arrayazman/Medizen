<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use App\Models\Doctor;
use App\Models\Radiographer;
use App\Models\Modality;
use App\Models\ExaminationType;
use App\Models\Room;
use App\Services\AuditService;
use App\Services\WorklistService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * Display the sampling queue (patients waiting for sample).
     */
    public function sampling(Request $request)
    {
        $today = Carbon::today();

        // Patients who need sampling: STATUS_ORDERED, STATUS_SENT_TO_PACS, or STATUS_WAITING_SAMPLE
        // and haven't had their sample taken yet.
        $queue = RadiologyOrder::with(['patient', 'examinationType', 'room', 'referringDoctor'])
            ->whereDate('scheduled_date', $today)
            ->whereIn('status', [
                RadiologyOrder::STATUS_ORDERED,
                RadiologyOrder::STATUS_SENT_TO_PACS,
                RadiologyOrder::STATUS_WAITING_SAMPLE
            ])
            ->orderBy('scheduled_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Stats for the header
        $stats = [
            'waiting' => $queue->count(),
            'completed_today' => RadiologyOrder::whereDate('scheduled_date', $today)
                ->whereNotIn('status', [
                    RadiologyOrder::STATUS_ORDERED,
                    RadiologyOrder::STATUS_SENT_TO_PACS,
                    RadiologyOrder::STATUS_WAITING_SAMPLE,
                    RadiologyOrder::STATUS_CANCELLED
                ])->count(),
        ];

        return view('queue.sampling', compact('queue', 'stats'));
    }

    /**
     * Dedicated fullscreen display for patients.
     */
    public function display()
    {
        $setting = \App\Models\InstitutionSetting::first();
        if ($setting && $setting->display_gallery_id) {
            $activeGallery = \App\Models\Gallery::with('items')->find($setting->display_gallery_id);
        } else {
            $activeGallery = \App\Models\Gallery::with('items')->where('is_active', true)->first();
        }

        return view('queue.display', compact('activeGallery', 'setting'));
    }

    /**
     * API endpoint for auto-refreshing the queue data.
     */
    public function apiSampling()
    {
        $today = Carbon::today();

        // Patients waiting
        $waiting = RadiologyOrder::with(['patient', 'examinationType', 'room'])
            ->whereDate('scheduled_date', $today)
            ->whereIn('status', [
                RadiologyOrder::STATUS_ORDERED,
                RadiologyOrder::STATUS_SENT_TO_PACS,
                RadiologyOrder::STATUS_WAITING_SAMPLE
            ])
            ->orderBy('scheduled_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Currently Calling: The most recently updated SAMPLE_TAKEN within a reasonable window (e.g., last 10 mins)
        // (Used for the center hero display)
        $calling = RadiologyOrder::with(['patient', 'examinationType', 'room'])
            ->whereDate('scheduled_date', $today)
            ->where('status', RadiologyOrder::STATUS_SAMPLE_TAKEN)
            ->where('waktu_sample', '>=', now()->subMinutes(15))
            ->orderBy('waktu_sample', 'desc')
            ->first();

        // Recently Completed: Patients who have truly finished their service
        // (COMPLETED, REPORTED, or VALIDATED status)
        $completed = RadiologyOrder::with(['patient', 'examinationType', 'room'])
            ->whereDate('scheduled_date', $today)
            ->whereIn('status', [
                RadiologyOrder::STATUS_COMPLETED,
                RadiologyOrder::STATUS_REPORTED,
                RadiologyOrder::STATUS_VALIDATED
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Active Gallery Data (Priority: Setting > is_active flag)
        $setting = \App\Models\InstitutionSetting::first();
        $galleryId = null;
        if ($setting && $setting->display_gallery_id) {
            $galleryId = $setting->display_gallery_id;
        } else {
            $active = \App\Models\Gallery::where('is_active', true)->first(['id']);
            $galleryId = $active ? $active->id : null;
        }

        $activeGallery = $galleryId ? \App\Models\Gallery::find($galleryId, ['id', 'type', 'name']) : null;

        return response()->json([
            'waiting' => $waiting,
            'calling' => $calling,
            'completed' => $completed,
            'gallery' => $activeGallery,
            'server_time' => now()->format('H:i:s'),
        ]);
    }
}
