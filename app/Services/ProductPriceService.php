<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductPriceService
{
    public function applyPercentageAdjustment(float $percentage): array
    {
        $multiplier = 1 + ($percentage / 100);
        $affected = 0;

        Product::query()->orderBy('id')->chunkById(100, function ($products) use ($multiplier, &$affected) {
            foreach ($products as $product) {
                DB::transaction(function () use ($product, $multiplier, &$affected) {
                    $locked = Product::lockForUpdate()->find($product->id);

                    if (! $locked) {
                        return;
                    }

                    if ($locked->original_price === null) {
                        $locked->original_price = $locked->price;
                    }

                    $locked->price = round((float) $locked->price * $multiplier, 2);
                    $locked->save();
                    $affected++;
                });
            }
        });

        return [
            'percentage' => $percentage,
            'affected' => $affected,
        ];
    }
}
