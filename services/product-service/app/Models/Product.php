<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'price',
        'stock_quantity',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active'      => 'boolean',
    ];
}
