<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.key' => 'test_api_key']);
    }

    #[Test]
    public function it_returns_product_summary(): void
    {
        Product::create([
            'id' => 1,
            'merchant_id' => 'm1',
            'name' => 'Product A',
            'link' => 'https://example.com/a',
            'image_link' => 'https://example.com/a.jpg',
            'price' => 100.00,
            'currency' => 'EUR',
        ]);

        Product::create([
            'id' => 2,
            'merchant_id' => 'm2',
            'name' => 'Product B',
            'link' => 'https://example.com/b',
            'image_link' => 'https://example.com/b.jpg',
            'price' => 50.00,
            'currency' => 'USD',
        ]);

        $response = $this->withHeader('X-API-Key', 'test_api_key')
            ->getJson('/api/products/summary');

        $response->assertOk()
            ->assertJson([
                'count' => 2,
                'total_price' => 150.00,
                'average_price' => 75.00,
                'currencies' => [
                    'EUR' => 1,
                    'USD' => 1,
                ],
            ]);
    }

    #[Test]
    public function it_rejects_requests_without_api_key(): void
    {
        $response = $this->getJson('/api/products/summary');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_filters_products_by_currency_and_price(): void
    {
        Product::create([
            'id' => 10,
            'merchant_id' => 'm1',
            'name' => 'EUR Product',
            'link' => 'https://example.com/eur',
            'image_link' => 'https://example.com/eur.jpg',
            'price' => 150.00,
            'currency' => 'EUR',
        ]);

        Product::create([
            'id' => 11,
            'merchant_id' => 'm1',
            'name' => 'USD Product',
            'link' => 'https://example.com/usd',
            'image_link' => 'https://example.com/usd.jpg',
            'price' => 200.00,
            'currency' => 'USD',
        ]);

        $response = $this->withHeader('X-API-Key', 'test_api_key')
            ->getJson('/api/products?currency=EUR&min_price=100&max_price=500');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 10);
    }
}
