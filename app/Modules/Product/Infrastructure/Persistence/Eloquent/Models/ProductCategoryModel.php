<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductCategoryModel extends BaseModel
{
    use HasTenant;

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
        'image_path',
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
