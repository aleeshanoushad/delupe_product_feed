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
                    // Use a DB table-level select with FOR UPDATE to ensure we get a row lock
                    $row = DB::table('products')
                        ->where('id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $row) {
                        return;
                    }

                    $currentPrice = (float) $row->price;
                    $originalPrice = $row->original_price !== null ? (float) $row->original_price : $currentPrice;

                    $newPrice = round($currentPrice * $multiplier, 2);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'price' => $newPrice,
                            'original_price' => $originalPrice,
                            'updated_at' => now(),
                        ]);

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
