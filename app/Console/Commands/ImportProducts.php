<?php

namespace App\Console\Commands;

use App\Services\ProductImportService;
use Illuminate\Console\Command;

class ImportProducts extends Command
{
    protected $signature = 'app:import-products {file : Path to the JSON products file}';

    protected $description = 'Import products from a JSON file';

    public function handle(ProductImportService $importService): int
    {
        $filePath = $this->argument('file');

        try {
            $result = $importService->importFromFile($filePath);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Import summary:');
        $this->line("  Imported: {$result['imported']}");
        $this->line("  Updated:  {$result['updated']}");
        $this->line("  Failed:   {$result['failed']}");

        if ($result['errors'] !== []) {
            $this->newLine();
            $this->warn('Failed records:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        return self::SUCCESS;
    }
}
