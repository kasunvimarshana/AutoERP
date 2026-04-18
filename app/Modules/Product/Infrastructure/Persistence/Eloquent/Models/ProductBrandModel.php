<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductBrandModel extends Model
{
    use HasAudit;

    protected $table = 'product_brands';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'code',
        'path',
        'depth',
        'is_active',
        'website',
        'description',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'depth' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'array',
        'metadata' => 'array',
    ];
}
