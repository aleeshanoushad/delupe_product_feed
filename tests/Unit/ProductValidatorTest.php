<?php

namespace Tests\Unit;

use App\Services\ProductValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductValidatorTest extends TestCase
{
    private ProductValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductValidator;
    }

    #[Test]
    public function it_passes_validation_for_valid_product(): void
    {
        $errors = $this->validator->validate([
            'id' => 1,
            'merchant_id' => 'merchant_001',
            'name' => 'Valid Product',
            'link' => 'https://example.com/product',
            'image_link' => 'https://example.com/image.jpg',
            'price' => 99.99,
            'currency' => 'EUR',
        ]);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_rejects_empty_product_name(): void
    {
        $errors = $this->validator->validate([
            'id' => 1,
            'merchant_id' => 'merchant_001',
            'name' => '',
            'link' => 'https://example.com/product',
            'image_link' => 'https://example.com/image.jpg',
            'price' => 99.99,
            'currency' => 'EUR',
        ]);

        $this->assertContains('Product name cannot be empty.', $errors);
    }

    #[Test]
    public function it_rejects_zero_or_negative_price(): void
    {
        $errors = $this->validator->validate([
            'id' => 1,
            'merchant_id' => 'merchant_001',
            'name' => 'Product',
            'link' => 'https://example.com/product',
            'image_link' => 'https://example.com/image.jpg',
            'price' => 0,
            'currency' => 'EUR',
        ]);

        $this->assertContains('Price must be greater than zero.', $errors);
    }

    #[Test]
    public function it_rejects_invalid_currency_code(): void
    {
        $errors = $this->validator->validate([
            'id' => 1,
            'merchant_id' => 'merchant_001',
            'name' => 'Product',
            'link' => 'https://example.com/product',
            'image_link' => 'https://example.com/image.jpg',
            'price' => 10,
            'currency' => 'INVALID',
        ]);

        $this->assertContains('Currency must be a valid ISO currency code (e.g. EUR, USD, GBP).', $errors);
    }

    #[Test]
    public function it_accepts_valid_iso_currency_codes(): void
    {
        $this->assertTrue($this->validator->isValidCurrency('EUR'));
        $this->assertTrue($this->validator->isValidCurrency('usd'));
        $this->assertTrue($this->validator->isValidCurrency('GBP'));
        $this->assertFalse($this->validator->isValidCurrency('XXX'));
    }
}
