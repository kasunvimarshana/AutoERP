<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductCategoryModel extends Model
{
    use HasAudit;

    protected $table = 'product_categories';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'code',
        'path',
        'depth',
        'is_active',
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
