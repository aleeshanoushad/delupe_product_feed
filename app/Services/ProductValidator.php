<?php

namespace App\Services;

class ProductValidator
{
    private const VALID_CURRENCIES = [
        'EUR', 'USD', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'NZD',
        'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN',
        'HRK', 'RUB', 'TRY', 'BRL', 'MXN', 'INR', 'CNY', 'KRW',
        'SGD', 'HKD', 'ZAR', 'AED', 'SAR', 'ILS',
    ];

    public function validate(array $record): array
    {
        $errors = [];

        if (! isset($record['id']) || ! is_numeric($record['id'])) {
            $errors[] = 'Product ID is required and must be numeric.';
        }

        if (! isset($record['merchant_id']) || trim((string) $record['merchant_id']) === '') {
            $errors[] = 'Merchant ID is required.';
        }

        if (! isset($record['name']) || trim((string) $record['name']) === '') {
            $errors[] = 'Product name cannot be empty.';
        }

        if (! isset($record['price']) || ! is_numeric($record['price']) || (float) $record['price'] <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }

        if (! isset($record['currency']) || ! $this->isValidCurrency((string) $record['currency'])) {
            $errors[] = 'Currency must be a valid ISO currency code (e.g. EUR, USD, GBP).';
        }

        if (! isset($record['link']) || trim((string) $record['link']) === '') {
            $errors[] = 'Product link is required.';
        }

        if (! isset($record['image_link']) || trim((string) $record['image_link']) === '') {
            $errors[] = 'Image link is required.';
        }

        return $errors;
    }

    public function isValidCurrency(string $currency): bool
    {
        return in_array(strtoupper(trim($currency)), self::VALID_CURRENCIES, true);
    }
}
