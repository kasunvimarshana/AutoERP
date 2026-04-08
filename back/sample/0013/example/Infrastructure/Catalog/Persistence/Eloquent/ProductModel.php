<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * ProductModel — Eloquent read/write model for the Catalog context.
 *
 * This is an infrastructure concern only. The domain entity (Product) is
 * mapped to/from this model in the repository layer.
 */
final class ProductModel extends Model
{
    protected $table      = 'catalog_products';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'id',
        'name',
        'price_amount',
        'price_currency',
        'active',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'price_amount' => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];
}
