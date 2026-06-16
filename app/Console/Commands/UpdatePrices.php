<?php

namespace App\Console\Commands;

use App\Services\ProductPriceService;
use Illuminate\Console\Command;

class UpdatePrices extends Command
{
    protected $signature = 'app:update-prices {percentage : Percentage to adjust prices by}';

    protected $description = 'Update all product prices by a percentage';

    public function handle(ProductPriceService $priceService): int
    {
        $percentage = (float) $this->argument('percentage');

        $result = $priceService->applyPercentageAdjustment($percentage);

        $this->info('Price adjustment summary:');
        $this->line("  Percentage: {$result['percentage']}%");
        $this->line("  Affected products: {$result['affected']}");

        return self::SUCCESS;
    }
}
