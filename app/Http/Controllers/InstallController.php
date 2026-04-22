<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    public function index()
    {
        try {
            // Check if already installed
            if (Schema::hasTable('users') && DB::table('users')->count() > 0) {
                return redirect('/');
            }
        } catch (\Exception $e) {
            // Likely no connection or no database, continue to install page
        }

        $envData = [];
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\' ');
                    $envData[$key] = $value;
                }
            }
        }

        // Grouping for better UI
        $groups = [
            'Aplikasi' => ['APP_NAME', 'APP_ENV', 'APP_URL', 'APP_DESCRIPTION'],
            'Database Utama' => ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
            'Database SIMRS' => ['DB_SIMRS_HOST', 'DB_SIMRS_PORT', 'DB_SIMRS_DATABASE', 'DB_SIMRS_USERNAME', 'DB_SIMRS_PASSWORD'],
            'PACS / Orthanc' => ['PACS_URL', 'PACS_USERNAME', 'PACS_PASSWORD', 'PACS_PUBLIC_URL'],
            'SatuSehat' => ['SATUSEHAT_ENV', 'SATUSEHAT_ORGANIZATION_ID', 'SATUSEHAT_CLIENT_ID', 'SATUSEHAT_CLIENT_SECRET'],
            'Lainnya' => []
        ];

        // Fill "Lainnya" with remaining keys
        $groupedData = [];
        $assignedKeys = [];
        foreach ($groups as $groupName => $keys) {
            foreach ($keys as $key) {
                if (isset($envData[$key])) {
                    $groupedData[$groupName][$key] = $envData[$key];
                    $assignedKeys[] = $key;
                }
            }
        }
        
        foreach ($envData as $key => $value) {
            if (!in_array($key, $assignedKeys) && $key !== 'APP_KEY' && strpos($key, 'VITE_') === false) {
                $groupedData['Lainnya'][$key] = $value;
            }
        }

        return view('install', compact('groupedData'));
    }

    public function updateEnv(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);
        
        // Force APP_NAME to Medizen
        $data['APP_NAME'] = 'Medizen';

        try {
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $lines = file($envPath);
                $newLines = [];
                foreach ($lines as $line) {
                    $matched = false;
                    foreach ($data as $key => $value) {
                        $keyUpper = strtoupper($key);
                        if (preg_match("/^{$keyUpper}=/", $line)) {
                            $finalValue = (strpos($value, ' ') !== false || strpos($value, '#') !== false) ? "\"$value\"" : $value;
                            $newLines[] = "{$keyUpper}={$finalValue}\n";
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        $newLines[] = $line;
                    }
                }
                file_put_contents($envPath, implode('', $newLines));
            }

            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui .env: ' . $e->getMessage()
            ], 500);
        }
    }

    public function run(Request $request)
    {
        set_time_limit(300);

        try {
            // 1. Run Migrations
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();

            // 2. Run Seeders
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = Artisan::output();

            // 3. Initialize Institution Settings from .env
            if (\App\Models\InstitutionSetting::count() === 0) {
                \App\Models\InstitutionSetting::create([
                    'hospital_name' => config('app.name', 'MEDIZEN RIS'),
                    'website' => config('app.url', 'localhost'),
                    'email' => 'admin@' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Instalasi berhasil! Menyiapkan data dari .env...',
                'redirect_to' => route('about'),
                'details' => $migrateOutput . "\n" . $seedOutput
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instalasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
