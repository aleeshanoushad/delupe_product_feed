<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductImportWithJobService
{
    public function __construct(
        private readonly ProductValidator $validator,
    ) {}

    public function importSingle(array $record): array
{

        $productId = $record['id'] ?? null;
        Log::info('Product import started', [
            'product_id' => $productId,
        ]);

    $validationErrors = $this->validator->validate($record);

    if ($validationErrors !== []) {
         Log::warning('Product validation failed', [
                'product_id' => $productId,
                'merchant_id' => $record['merchant_id'] ?? null,
                'errors' => $validationErrors,
                'record' => $record,
            ]);
        return [
            'status' => 'failed',
            'error' => implode(' ', $validationErrors)
        ];
    }

    $productData = [
        'merchant_id' => (string) $record['merchant_id'],
        'name' => (string) $record['name'],
        'link' => (string) $record['link'],
        'image_link' => (string) $record['image_link'],
        'price' => round((float) $record['price'], 2),
        'currency' => strtoupper((string) $record['currency']),
    ];

     $existing = Product::find($productId);

        if ($existing) {
            $existing->update($productData);

            Log::info('Product updated', [
                'product_id' => $productId,
            ]);

            return [
                'status' => 'updated',
            ];
        }

        Product::create(
            array_merge(['id' => (int) $productId], $productData)
        );
        Log::info('Product imported', [
            'product_id' => $productId,
        ]);

       

    return [
        'status' => 'imported',
    ];
}

    
}
