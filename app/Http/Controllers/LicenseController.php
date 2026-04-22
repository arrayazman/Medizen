<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LicenseController extends Controller
{
    /**
     * Endpoint for engine.bootstrap.js to get local institutional data.
     */
    public function getInstitutionData(Request $request)
    {
        $setting = InstitutionSetting::first() ?? new InstitutionSetting();
        $domain = $request->getHost();

        $data = [
            'institution_name' => $setting->hospital_name ?? 'NOT_SET',
            'institution_email' => $setting->email ?? 'NOT_SET',
            'institution_phone' => $setting->phone ?? 'NOT_SET',
            'institution_domain' => $setting->website ?? $domain,
            'license_code' => $setting->app_license_key ?? 'NONE',
            'rtdb_url' => config('services.firebase.database_url') ?? 'https://undefined.firebaseio.com/',
        ];

        // Add integrity hash for basic validation
        $data['local_hash'] = hash('sha256', implode('|', array_values($data)) . '|APP_SALT_V1');

        return response()->json($data);
    }

    /**
     * Submit activation request to Firebase Cloud Function.
     */
    public function requestActivation(Request $request)
    {
        $request->validate([
            'institution_name' => 'required|string',
            'institution_email' => 'required|email',
            'institution_phone' => 'required|string',
        ]);

        $setting = InstitutionSetting::first() ?? new InstitutionSetting();
        $domain = $request->getHost();

        // Strict Domain Verification
        if ($request->filled('institution_domain') && $request->institution_domain !== $domain) {
            return response()->json([
                'success' => false,
                'message' => 'Integrasi Domain Gagal: Domain yang didaftarkan (' . $request->institution_domain . ') tidak sesuai dengan URL sistem yang sedang berjalan (' . $domain . ').'
            ], 400);
        }

        $licenseCode = $setting->app_license_key;
        $email = trim(strtolower($request->institution_email));
        $baseUrl = rtrim(config('services.firebase.database_url'), '/') . '/activation_requests.json';

        try {
            // Using query parameters array is safer and avoids "JSON primitive" encoding issues
            $checkResponse = Http::timeout(10)->withoutVerifying()->get($baseUrl, [
                'orderBy' => '"institution_email"',
                'equalTo' => '"' . $email . '"'
            ]);

            if (!$checkResponse->successful()) {
                // If the check fails (e.g. 400 Bad Request due to missing index), stop here
                $errorMsg = $checkResponse->json()['error'] ?? 'Gagal memvalidasi email di server.';
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengecek status pendaftaran: ' . $errorMsg
                ], 400);
            }

            $existingData = $checkResponse->json();

            if (!empty($existingData)) {
                $firstKey = array_key_first($existingData);
                $existing = $existingData[$firstKey];

                return response()->json([
                    'success' => false,
                    'message' => 'Email ini (' . $email . ') sudah terdaftar. Silakan hubungi Administrator untuk aktivasi lebih lanjut.'
                ], 422);
            }

            // Normal Flow: Generate new key if none exists locally
            if (!$licenseCode || $licenseCode == 'NONE') {
                $licenseCode = 'MEDIZEN-' . strtoupper(\Illuminate\Support\Str::random(4) . '-' . \Illuminate\Support\Str::random(4) . '-' . \Illuminate\Support\Str::random(4));
            }

            // Send directly to Realtime Database REST API
            $rtdbUrl = config('services.firebase.database_url') . 'activation_requests.json';
            $defaultExpiry = now()->addYear()->timestamp * 1000; // 1 year from now in ms

            $response = Http::withoutVerifying()->post($rtdbUrl, [
                'license_code' => $licenseCode,
                'institution_name' => $request->institution_name,
                'institution_email' => $request->institution_email,
                'institution_phone' => $request->institution_phone,
                'institution_domain' => $domain,
                'status' => 'active',
                'max_users' => 20,           // Default value
                'expired_at' => $defaultExpiry, // Default value (1 year)
                'created_at' => now()->timestamp * 1000,
            ]);

            if ($response->successful()) {
                // Save locally
                $setting->app_license_key = $licenseCode;
                $setting->hospital_name = $request->institution_name;
                $setting->email = $request->institution_email;
                $setting->phone = $request->institution_phone;
                $setting->website = $domain; // Keep local website in sync with activation domain
                $setting->save();
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync verified data from Runtime JS to Local DB.
     */
    public function syncLicenseData(Request $request)
    {
        // No longer saving is_pro, max_users, or expired_at locally.
        // These are strictly managed in the Frontend Runtime via Firebase.

        \Log::info("License Runtime Sync Attempt: " . ($request->status ?? 'unknown') . " for " . $request->license_code);

        return response()->json(['success' => true, 'message' => 'Status sync acknowledged (Database use disabled)']);
    }
}
