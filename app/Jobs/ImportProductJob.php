<?php

namespace App\Jobs;

use App\Services\ProductImportWithJobService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public array $record
    ) {}

    public function handle(ProductImportWithJobService $service): void
    {
        $result = $service->importSingle($this->record);
        $batchId = $this->batchId();

        Log::info('Product job processed', [
            'product_id' => $this->record['id'] ?? null,
            'status' => $result['status'] ?? 'unknown',
            'batch_id' => $batchId,
        ]);

        cache()->increment("import:{$batchId}:total");
        cache()->increment("import:{$batchId}:processed");

        if (($result['status'] ?? null) === 'imported') {
            cache()->increment("import:{$batchId}:imported");
        }

        if (($result['status'] ?? null) === 'updated') {
            cache()->increment("import:{$batchId}:updated");
        }

        if (($result['status'] ?? null) === 'failed') {
            cache()->increment("import:{$batchId}:failed");
            $this->appendBatchError($batchId, $result['error'] ?? 'Unknown error');
        }

        Log::info('Import progress update', [
            'batch_id' => $batchId,
            'processed' => cache()->get("import:{$batchId}:processed", 0),
            'imported' => cache()->get("import:{$batchId}:imported", 0),
            'updated' => cache()->get("import:{$batchId}:updated", 0),
            'failed' => cache()->get("import:{$batchId}:failed", 0),
        ]);
    }

    private function appendBatchError(string $batchId, string $error): void
    {
        $errors = cache()->get("import:{$batchId}:errors", []);

        if (! is_array($errors)) {
            $errors = [];
        }

        $errors[] = $error;

        cache()->put("import:{$batchId}:errors", $errors, now()->addHours(2));
    }

    private function batchId()
    {
        return $this->job?->batchId ?? 'no-batch';
    }
}