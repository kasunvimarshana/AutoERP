<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductAttachmentModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'product_attachments';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'type',
        'is_primary',
        'sort_order',
        'title',
        'description',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'file_size' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];
}
