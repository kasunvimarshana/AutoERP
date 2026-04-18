<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductVariantModel extends Model
{
    use HasAudit;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
