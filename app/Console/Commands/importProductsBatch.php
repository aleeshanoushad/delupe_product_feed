<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductJob;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ImportProductsBatch extends Command
{
    protected $signature = 'app:import-products-batch {file}';

    protected $description = 'Import products using Bus::batch';

    public function handle(): int
    {
        $file = $this->argument('file');

        $records = json_decode(file_get_contents($file), true);

        if (!is_array($records)) {
            $this->error('Invalid JSON');
            return self::FAILURE;
        }

        $this->info('Starting batch import...');

        $batch = Bus::batch([])->dispatch();

        Cache::put("import:{$batch->id}:total", 0);
        Cache::put("import:{$batch->id}:imported", 0);
        Cache::put("import:{$batch->id}:updated", 0);
        Cache::put("import:{$batch->id}:failed", 0);
        Cache::put("import:{$batch->id}:errors", []);

        $jobs = [];

        foreach ($records as $record) {
            $jobs[] = new ImportProductJob($record);
        }

        $batch->add($jobs);

        $this->info("Batch started: {$batch->id}");

        return self::SUCCESS;
    }
}