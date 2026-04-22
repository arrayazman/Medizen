<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UpdateController extends Controller
{
    protected $repoUrl = 'https://raw.githubusercontent.com/arrayazman/Medizen/main/VERSION';
    
    /**
     * Check for updates against GitHub
     */
    public function check()
    {
        try {
            $localVersion = trim(File::get(base_path('VERSION')));
            $response = Http::timeout(5)->get($this->repoUrl);
            
            if (!$response->successful()) {
                return response()->json(['update_available' => false, 'error' => 'Gagal menghubungi GitHub']);
            }
            
            $remoteVersion = trim($response->body());
            $updateAvailable = version_compare($remoteVersion, $localVersion, '>');
            
            return response()->json([
                'local_version' => $localVersion,
                'remote_version' => $remoteVersion,
                'update_available' => $updateAvailable
            ]);
        } catch (\Exception $e) {
            return response()->json(['update_available' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Perform the update process
     */
    public function run()
    {
        // Require super_admin or it_support
        if (!auth()->user() || !in_array(auth()->user()->role, ['super_admin', 'it_support'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        set_time_limit(600); // 10 minutes max
        
        $logs = [];
        try {
            // 1. Maintenance Mode
            Artisan::call('down', ['--refresh' => 15, '--secret' => 'medizen-update']);
            $logs[] = "System entered maintenance mode.";

            // 2. Git Pull (Assuming git is installed)
            $output = [];
            $returnVar = 0;
            exec('git pull origin main 2>&1', $output, $returnVar);
            $logs[] = implode("\n", $output);

            if ($returnVar !== 0) {
                Artisan::call('up');
                return response()->json(['success' => false, 'message' => 'Git pull failed.', 'logs' => $logs]);
            }

            // 3. Database Migration
            Artisan::call('migrate', ['--force' => true]);
            $logs[] = "Database migrations executed: " . Artisan::output();

            // 4. Cache Clear
            Artisan::call('optimize:clear');
            $logs[] = "System cache cleared.";

            // 5. Version Sync (Optional redundant check)
            $newVersion = trim(File::get(base_path('VERSION')));
            $logs[] = "Updated to version: " . $newVersion;

            // 6. Maintenance Mode Off
            Artisan::call('up');
            $logs[] = "System is back online.";

            return response()->json([
                'success' => true, 
                'message' => 'Update berhasil diselesaikan!',
                'version' => $newVersion,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            Artisan::call('up');
            return response()->json([
                'success' => false, 
                'message' => 'Update gagal: ' . $e->getMessage(),
                'logs' => $logs
            ], 500);
        }
    }
}
