<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Radiographer;
use App\Models\Modality;
use App\Models\ExaminationType;
use App\Models\Room;
use App\Models\Patient;
use App\Models\RadiologyOrder;
use App\Models\RadiologyResult;
use App\Helpers\DicomHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ========================
        // USERS
        // ========================
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@rsi.com', 'role' => 'super_admin'],
            ['name' => 'Admin Radiologi', 'email' => 'admin@rsi.com', 'role' => 'admin_radiologi'],
            ['name' => 'Radiografer 1', 'email' => 'radiografer@rsi.com', 'role' => 'radiografer'],
            ['name' => 'Dr. Ahmad Radiolog', 'email' => 'dokter@rsi.com', 'role' => 'dokter_radiologi'],
            ['name' => 'Direktur RSI', 'email' => 'direktur@rsi.com', 'role' => 'direktur'],
            ['name' => 'IT Support', 'email' => 'it@rsi.com', 'role' => 'it_support'],
        ];

        foreach ($users as $u) {
            User::create(array_merge($u, [
                'password' => Hash::make('password'),
                'is_active' => true,
            ]));
        }

        // ========================
        // DOCTORS
        // ========================
        $doctors = [
            ['name' => 'dr. Ahmad Fauzi, Sp.Rad', 'specialization' => 'Radiologi', 'sip_number' => 'SIP-RAD-001', 'phone' => '081234567890', 'user_id' => 4],
            ['name' => 'dr. Siti Aminah, Sp.Rad', 'specialization' => 'Radiologi', 'sip_number' => 'SIP-RAD-002', 'phone' => '081234567891'],
            ['name' => 'dr. Budi Santoso, Sp.PD', 'specialization' => 'Penyakit Dalam', 'sip_number' => 'SIP-PD-001', 'phone' => '081234567892'],
            ['name' => 'dr. Dewi Lestari, Sp.B', 'specialization' => 'Bedah', 'sip_number' => 'SIP-B-001', 'phone' => '081234567893'],
            ['name' => 'dr. Rudi Hermawan, Sp.OG', 'specialization' => 'Obgyn', 'sip_number' => 'SIP-OG-001', 'phone' => '081234567894'],
        ];
        foreach ($doctors as $d) {
            Doctor::create($d);
        }

        // ========================
        // RADIOGRAPHERS
        // ========================
        $radiographers = [
            ['name' => 'Andi Prasetyo', 'sip_number' => 'SIP-RG-001', 'phone' => '082345678901', 'user_id' => 3],
            ['name' => 'Fitri Handayani', 'sip_number' => 'SIP-RG-002', 'phone' => '082345678902'],
            ['name' => 'Hasan Basri', 'sip_number' => 'SIP-RG-003', 'phone' => '082345678903'],
        ];
        foreach ($radiographers as $r) {
            Radiographer::create($r);
        }

        // ========================
        // MODALITIES
        // ========================
        $modalities = [
            ['code' => 'CT', 'name' => 'CT Scan', 'ae_title' => 'CT', 'description' => 'Computed Tomography'],
            ['code' => 'MR', 'name' => 'MRI', 'ae_title' => 'MR', 'description' => 'Magnetic Resonance Imaging'],
            ['code' => 'CR', 'name' => 'Computed Radiography', 'ae_title' => 'CR', 'description' => 'Computed Radiography'],
            ['code' => 'DR', 'name' => 'Digital Radiography', 'ae_title' => 'DR', 'description' => 'Digital Radiography / X-Ray'],
            ['code' => 'US', 'name' => 'Ultrasonography', 'ae_title' => 'US', 'description' => 'Ultrasonography / USG'],
        ];
        foreach ($modalities as $m) {
            Modality::create($m);
        }

        // ========================
        // EXAMINATION TYPES
        // ========================
        $examTypes = [
            ['modality_id' => 1, 'code' => 'CT-HEAD', 'name' => 'CT Scan Kepala', 'duration_minutes' => 30],
            ['modality_id' => 1, 'code' => 'CT-THORAX', 'name' => 'CT Scan Thorax', 'duration_minutes' => 30],
            ['modality_id' => 1, 'code' => 'CT-ABDOMEN', 'name' => 'CT Scan Abdomen', 'duration_minutes' => 45],
            ['modality_id' => 2, 'code' => 'MR-BRAIN', 'name' => 'MRI Brain', 'duration_minutes' => 45],
            ['modality_id' => 2, 'code' => 'MR-SPINE', 'name' => 'MRI Spine', 'duration_minutes' => 45],
            ['modality_id' => 3, 'code' => 'CR-THORAX', 'name' => 'Rontgen Thorax', 'duration_minutes' => 10],
            ['modality_id' => 3, 'code' => 'CR-ABDOMEN', 'name' => 'Rontgen Abdomen', 'duration_minutes' => 10],
            ['modality_id' => 4, 'code' => 'DR-THORAX', 'name' => 'DR Thorax PA', 'duration_minutes' => 10],
            ['modality_id' => 4, 'code' => 'DR-EXTR', 'name' => 'DR Extremitas', 'duration_minutes' => 15],
            ['modality_id' => 5, 'code' => 'US-ABDOMEN', 'name' => 'USG Abdomen', 'duration_minutes' => 20],
            ['modality_id' => 5, 'code' => 'US-OBSTETRI', 'name' => 'USG Obstetri', 'duration_minutes' => 20],
        ];
        foreach ($examTypes as $et) {
            ExaminationType::create($et);
        }

        // ========================
        // ROOMS
        // ========================
        $rooms = [
            ['name' => 'Ruang CT Scan', 'code' => 'R-CT-01', 'modality_id' => 1, 'floor' => 'Lantai 1'],
            ['name' => 'Ruang MRI', 'code' => 'R-MR-01', 'modality_id' => 2, 'floor' => 'Lantai 1'],
            ['name' => 'Ruang Rontgen 1', 'code' => 'R-CR-01', 'modality_id' => 3, 'floor' => 'Lantai 1'],
            ['name' => 'Ruang Rontgen 2', 'code' => 'R-DR-01', 'modality_id' => 4, 'floor' => 'Lantai 1'],
            ['name' => 'Ruang USG', 'code' => 'R-US-01', 'modality_id' => 5, 'floor' => 'Lantai 2'],
        ];
        foreach ($rooms as $r) {
            Room::create($r);
        }

        // ========================
        // PATIENTS (50 dummy)
        // ========================
        $namaDepan = ['Ahmad', 'Siti', 'Budi', 'Dewi', 'Rudi', 'Rina', 'Hasan', 'Fatimah', 'Joko', 'Ani', 'Agus', 'Nurma', 'Dimas', 'Ratna', 'Eko', 'Sri', 'Bambang', 'Yuni', 'Rahmat', 'Lina', 'Wahyu', 'Mega', 'Irfan', 'Putri', 'Rizky'];
        $namaBelakang = ['Pratama', 'Sari', 'Wijaya', 'Lestari', 'Hidayat', 'Rahayu', 'Setiawan', 'Wati', 'Putra', 'Utami', 'Santoso', 'Handayani', 'Nugroho', 'Kusuma', 'Arifin', 'Susanti', 'Kurniawan', 'Fitriani', 'Saputra', 'Hartono', 'Maulana', 'Anggraeni', 'Fadillah', 'Indriani', 'Permana'];

        for ($i = 1; $i <= 50; $i++) {
            Patient::create([
                'no_rm' => 'RM' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'nik' => '327100' . str_pad($i, 10, '0', STR_PAD_LEFT),
                'nama' => $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)],
                'jenis_kelamin' => rand(0, 1) ? 'L' : 'P',
                'tgl_lahir' => Carbon::now()->subYears(rand(5, 80))->subDays(rand(1, 365))->format('Y-m-d'),
                'alamat' => 'Jl. Contoh No. ' . rand(1, 200) . ', Kota Contoh',
                'no_hp' => '09' . rand(1000000000, 9999999999),
            ]);
        }

        // ========================
        // RADIOLOGY ORDERS (20 dummy)
        // ========================
        $statuses = [
            RadiologyOrder::STATUS_ORDERED,
            RadiologyOrder::STATUS_SENT_TO_PACS,
            RadiologyOrder::STATUS_IN_PROGRESS,
            RadiologyOrder::STATUS_COMPLETED,
            RadiologyOrder::STATUS_REPORTED,
            RadiologyOrder::STATUS_VALIDATED
        ];
        $modalityCodes = ['CT', 'MR', 'CR', 'DR', 'US'];

        for ($i = 1; $i <= 20; $i++) {
            $mod = $modalityCodes[array_rand($modalityCodes)];
            $date = Carbon::today()->subDays(rand(0, 14));

            $status = $statuses[array_rand($statuses)];
            $waktuSample = null;
            $waktuHasil = null;
            $baseTime = Carbon::parse($date->format('Y-m-d') . ' ' . sprintf('%02d:%02d:00', rand(8, 16), rand(0, 59)));

            if (in_array($status, [RadiologyOrder::STATUS_SAMPLE_TAKEN, RadiologyOrder::STATUS_IN_PROGRESS, RadiologyOrder::STATUS_COMPLETED, RadiologyOrder::STATUS_REPORTED, RadiologyOrder::STATUS_VALIDATED])) {
                $waktuSample = (clone $baseTime)->addMinutes(rand(5, 30))->format('Y-m-d H:i:s');
            }
            if (in_array($status, [RadiologyOrder::STATUS_COMPLETED, RadiologyOrder::STATUS_REPORTED, RadiologyOrder::STATUS_VALIDATED])) {
                $waktuHasil = Carbon::parse($waktuSample)->addMinutes(rand(10, 120))->format('Y-m-d H:i:s');
            }

            $order = RadiologyOrder::create([
                'order_number' => DicomHelper::generateOrderNumber(),
                'accession_number' => DicomHelper::generateAccessionNumber(),
                'patient_id' => rand(1, 50),
                'modality' => $mod,
                'examination_type_id' => rand(1, 11),
                'referring_doctor_id' => rand(1, 5),
                'radiographer_id' => rand(1, 3),
                'study_instance_uid' => DicomHelper::generateStudyInstanceUID(),
                'scheduled_date' => $date->format('Y-m-d'),
                'scheduled_time' => $baseTime->format('H:i:s'),
                'station_ae_title' => $mod,
                'procedure_description' => 'Pemeriksaan ' . $mod . ' rutin',
                'priority' => ['ROUTINE', 'URGENT', 'STAT'][rand(0, 2)],
                'status' => $status,
                'waktu_sample' => $waktuSample,
                'room_id' => rand(1, 5),
                'created_by' => 2,
                'created_at' => $baseTime->format('Y-m-d H:i:s'),
            ]);

            if ($waktuHasil) {
                RadiologyResult::create([
                    'radiology_order_id' => $order->id,
                    'doctor_id' => $order->referring_doctor_id,
                    'expertise' => 'Hasil pemeriksaan ' . $mod . ' menunjukkan kondisi normal pada area yang diperiksa.',
                    'waktu_hasil' => $waktuHasil,
                    'status' => 'FINAL',
                ]);
            }
        }
    }
}
