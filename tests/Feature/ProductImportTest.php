<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_new_products_from_json_file(): void
    {
        $filePath = storage_path('app/test_import.json');
        file_put_contents($filePath, json_encode([
            [
                'id' => 101,
                'merchant_id' => 'merchant_001',
                'name' => 'Test Product',
                'link' => 'https://example.com/test',
                'image_link' => 'https://example.com/test.jpg',
                'price' => 50.00,
                'currency' => 'EUR',
            ],
        ]));

        Artisan::call('app:import-products', ['file' => $filePath]);

        $this->assertDatabaseHas('products', [
            'id' => 101,
            'name' => 'Test Product',
            'price' => 50.00,
            'currency' => 'EUR',
        ]);

        unlink($filePath);
    }

    #[Test]
    public function it_updates_existing_products_on_reimport(): void
    {
        Product::create([
            'id' => 102,
            'merchant_id' => 'merchant_001',
            'name' => 'Old Name',
            'link' => 'https://example.com/old',
            'image_link' => 'https://example.com/old.jpg',
            'price' => 30.00,
            'currency' => 'EUR',
        ]);

        $filePath = storage_path('app/test_update.json');
        file_put_contents($filePath, json_encode([
            [
                'id' => 102,
                'merchant_id' => 'merchant_001',
                'name' => 'Updated Name',
                'link' => 'https://example.com/new',
                'image_link' => 'https://example.com/new.jpg',
                'price' => 35.00,
                'currency' => 'USD',
            ],
        ]));

        Artisan::call('app:import-products', ['file' => $filePath]);

        $this->assertDatabaseHas('products', [
            'id' => 102,
            'name' => 'Updated Name',
            'price' => 35.00,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseCount('products', 1);

        unlink($filePath);
    }

    #[Test]
    public function it_skips_invalid_records_during_import(): void
    {
        $filePath = storage_path('app/test_invalid.json');
        file_put_contents($filePath, json_encode([
            [
                'id' => 103,
                'merchant_id' => 'merchant_001',
                'name' => '',
                'link' => 'https://example.com/invalid',
                'image_link' => 'https://example.com/invalid.jpg',
                'price' => 10,
                'currency' => 'EUR',
            ],
            [
                'id' => 104,
                'merchant_id' => 'merchant_001',
                'name' => 'Valid Product',
                'link' => 'https://example.com/valid',
                'image_link' => 'https://example.com/valid.jpg',
                'price' => 20,
                'currency' => 'GBP',
            ],
        ]));

        Artisan::call('app:import-products', ['file' => $filePath]);

        $this->assertDatabaseMissing('products', ['id' => 103]);
        $this->assertDatabaseHas('products', ['id' => 104]);

        unlink($filePath);
    }
}
