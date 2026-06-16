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

        Log::info('Product job processed', [
            'product_id' => $this->record['id'] ?? null,
            'status' => $result['status'] ?? 'unknown',
        ]);

        // store batch counters safely
        cache()->increment("import:{$this->batchId()}:total");

        if (($result['status'] ?? null) === 'imported') {
            cache()->increment("import:{$this->batchId()}:imported");
        }

        if (($result['status'] ?? null) === 'updated') {
            cache()->increment("import:{$this->batchId()}:updated");
        }

        if (($result['status'] ?? null) === 'failed') {
            cache()->increment("import:{$this->batchId()}:failed");

            cache()->push(
                "import:{$this->batchId()}:errors",
                $result['error'] ?? 'Unknown error'
            );
        }

        Log::info('Import progress update', [
        'batch_id' => $this->batchId(),
        'processed' => cache("import:{$this->batchId()}:processed"),
        'imported' => cache("import:{$this->batchId()}:imported"),
        'updated' => cache("import:{$this->batchId()}:updated"),
        'failed' => cache("import:{$this->batchId()}:failed"),
    ]);
    }

    private function batchId()
    {
        return $this->job?->batchId ?? 'no-batch';
    }
}