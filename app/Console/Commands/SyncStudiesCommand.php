<?php

namespace App\Console\Commands;

use App\Services\StudySyncService;
use Illuminate\Console\Command;

class SyncStudiesCommand extends Command
{
    protected $signature = 'ris:sync-studies';
    protected $description = 'Sync completed studies from PACS DICOM server';

    public function handle(StudySyncService $service): int
    {
        $this->info('Starting study sync from pacs...');

        $synced = $service->syncStudies();

        $this->info("Study sync completed. {$synced} studies synced.");

        return Command::SUCCESS;
    }
}


