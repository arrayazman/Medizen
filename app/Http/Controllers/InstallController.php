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
        // Pre-check basic requirement: Storage folders
        $requiredFolders = [
            storage_path('app/public'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache')
        ];

        $missingFolders = [];
        foreach ($requiredFolders as $folder) {
            if (!file_exists($folder)) {
                $missingFolders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $folder);
            }
        }

        if (!empty($missingFolders)) {
            return "<h3>Instalasi Belum Siap (Izin Folder)</h3>
                    <p>Folder berikut belum ada atau tidak bisa diakses. Silakan buat folder ini secara manual:</p>
                    <ul><li>" . implode("</li><li>", $missingFolders) . "</li></ul>
                    <p>Setelah dibuat, silakan <a href='/install'>Refresh Halaman</a>.</p>";
        }

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
        $envExamplePath = base_path('.env.example');
        
        // Use .env if exists, otherwise fallback to .env.example for defaults
        $targetPath = file_exists($envPath) ? $envPath : (file_exists($envExamplePath) ? $envExamplePath : null);

        if ($targetPath) {
            $lines = file($targetPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
            $envExamplePath = base_path('.env.example');

            // 1. If .env doesn't exist, try to create it from example
            if (!file_exists($envPath)) {
                if (file_exists($envExamplePath)) {
                    if (!copy($envExamplePath, $envPath)) {
                        throw new \Exception('Gagal menyalin .env.example ke .env. Pastikan folder root memiliki izin tulis.');
                    }
                } else {
                    throw new \Exception('File .env.example tidak ditemukan. Pastikan instalasi lengkap.');
                }
            }

            // 2. Check if writable
            if (!is_writable($envPath)) {
                throw new \Exception('File .env tidak dapat ditulis. Periksa izin akses (CHMOD) file tersebut.');
            }

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
            
            // Add missing keys if they were not in the file
            $newContent = implode('', $newLines);
            foreach ($data as $key => $value) {
                $keyUpper = strtoupper($key);
                if (strpos($newContent, "{$keyUpper}=") === false) {
                    $finalValue = (strpos($value, ' ') !== false || strpos($value, '#') !== false) ? "\"$value\"" : $value;
                    $newContent .= "{$keyUpper}={$finalValue}\n";
                }
            }

            file_put_contents($envPath, $newContent);

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi berhasil diperbarui!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage()
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
