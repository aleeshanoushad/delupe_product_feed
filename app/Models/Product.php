<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'merchant_id',
        'name',
        'link',
        'image_link',
        'price',
        'original_price',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
        ];
    }
}
