<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static int count()
 * @method static float|int sum(string $column)
 * @method static Product|null find(int|string $id)
 * @method static Product create(array $attributes = [])
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
    ];
}
