<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductImportService
{
    public function __construct(
        private readonly ProductValidator $validator,
    ) {}

    public function importFromFile(string $filePath): array
    {
        Log::info('Product import started.', ['file' => $filePath]);

        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $records = json_decode($content, true);

        if (! is_array($records)) {
            throw new \InvalidArgumentException('Invalid JSON file: expected an array of products.');
        }

        $imported = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            if (! is_array($record)) {
                $failed++;
                $message = "Record at index {$index} is not a valid object.";
                $errors[] = $message;
                Log::warning('Validation error during import.', ['index' => $index, 'error' => $message]);

                continue;
            }

            $validationErrors = $this->validator->validate($record);

            if ($validationErrors !== []) {
                $failed++;
                $message = 'Record '.($record['id'] ?? $index).': '.implode(' ', $validationErrors);
                $errors[] = $message;
                Log::warning('Validation error during import.', [
                    'index' => $index,
                    'product_id' => $record['id'] ?? null,
                    'errors' => $validationErrors,
                ]);

                continue;
            }

            $productData = [
                'merchant_id' => (string) $record['merchant_id'],
                'name' => (string) $record['name'],
                'link' => (string) $record['link'],
                'image_link' => (string) $record['image_link'],
                'price' => round((float) $record['price'], 2),
                'currency' => strtoupper((string) $record['currency']),
            ];

            $existing = Product::find($record['id']);

            if ($existing) {
                $existing->update($productData);
                $updated++;
            } else {
                Product::create(array_merge(['id' => (int) $record['id']], $productData));
                $imported++;
            }
        }

        Log::info('Product import completed.', [
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
        ]);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}