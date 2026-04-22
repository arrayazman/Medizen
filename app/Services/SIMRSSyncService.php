<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\RadiologyOrder;
use App\Models\RadiologyReport;
use Exception;
use Illuminate\Support\Facades\Log;

class SIMRSSyncService
{
    /**
     * Push radiology result to SIMRS tables
     * Tables: periksa_radiologi, hasil_radiologi
     */
    public function pushResult(RadiologyOrder $order)
    {
        if (!$order->report || !in_array($order->status, [RadiologyOrder::STATUS_REPORTED, RadiologyOrder::STATUS_VALIDATED])) {
            return false;
        }

        try {
            DB::connection('simrs')->beginTransaction();

            $report = $order->report;
            $noorder = $order->order_number;

            // Get original order data from SIMRS
            $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                ->where('noorder', $noorder)
                ->first();

            if (!$simrsOrder && $order->external_id) {
                $simrsOrder = DB::connection('simrs')->table('permintaan_radiologi')
                    ->where('noorder', $order->external_id)
                    ->first();
            }

            if (!$simrsOrder) {
                Log::warning("SIMRS Sync: Order $noorder not found in SIMRS.");
                return false;
            }

            // Determine date and time to use for SIMRS hasil
            // Use existing if already pushed (for updates), otherwise current time
            $tgl_pushed = ($simrsOrder->tgl_hasil != '0000-00-00') ? $simrsOrder->tgl_hasil : date('Y-m-d');
            $jam_pushed = ($simrsOrder->jam_hasil != '00:00:00') ? $simrsOrder->jam_hasil : date('H:i:s');

            // Get items for this order in SIMRS
            $orderItems = DB::connection('simrs')->table('permintaan_pemeriksaan_radiologi')
                ->where('noorder', $simrsOrder->noorder)
                ->get();

            foreach ($orderItems as $item) {
                // Insert/Update into periksa_radiologi
                DB::connection('simrs')->table('periksa_radiologi')->updateOrInsert(
                    [
                        'no_rawat' => $simrsOrder->no_rawat,
                        'tgl_periksa' => $tgl_pushed,
                        'jam' => $jam_pushed,
                        'kd_jenis_prw' => $item->kd_jenis_prw
                    ],
                    [
                        'nip' => '123124',
                        'dokter_perujuk' => $simrsOrder->dokter_perujuk,
                        'kd_dokter' => $order->report->dokter->kd_dokter ?? '-',
                        'biaya' => $item->biaya,
                        'status' => 'Sudah'
                    ]
                );
            }

            // Insert/Update into hasil_radiologi
            DB::connection('simrs')->table('hasil_radiologi')->updateOrInsert(
                [
                    'no_rawat' => $simrsOrder->no_rawat,
                    'tgl_periksa' => $tgl_pushed,
                    'jam' => $jam_pushed
                ],
                [
                    'hasil' => $report->hasil . "\n\nKesimpulan:\n" . $report->kesimpulan
                ]
            );

            // Update status in permintaan_radiologi to 'Sudah' (completed)
            DB::connection('simrs')->table('permintaan_radiologi')
                ->where('noorder', $simrsOrder->noorder)
                ->update(['tgl_hasil' => $tgl_pushed, 'jam_hasil' => $jam_pushed]);

            DB::connection('simrs')->commit();
            return true;

        } catch (Exception $e) {
            DB::connection('simrs')->rollBack();
            Log::error("SIMRS Sync Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Push sample time to SIMRS permintaan_radiologi table
     */
    public function pushSampleTime(RadiologyOrder $order)
    {
        try {
            $noorder = $order->order_number;

            $updated = DB::connection('simrs')->table('permintaan_radiologi')
                ->where('noorder', $noorder)
                ->update([
                    'tgl_sampel' => date('Y-m-d', strtotime($order->waktu_sample)),
                    'jam_sampel' => date('H:i:s', strtotime($order->waktu_sample))
                ]);

            if (!$updated && $order->external_id) {
                DB::connection('simrs')->table('permintaan_radiologi')
                    ->where('noorder', $order->external_id)
                    ->update([
                        'tgl_sampel' => date('Y-m-d', strtotime($order->waktu_sample)),
                        'jam_sampel' => date('H:i:s', strtotime($order->waktu_sample))
                    ]);
            }

            return true;
        } catch (Exception $e) {
            Log::error("SIMRS Sample Sync Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Map examination codes between RIS and SIMRS if necessary
     */
    public function mapExamination($risCode)
    {
        // Placeholder for mapping logic
        return $risCode;
    }
}
