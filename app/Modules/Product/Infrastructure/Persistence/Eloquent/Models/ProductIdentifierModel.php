<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductIdentifierModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'product_identifiers';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'technology',
        'format',
        'value',
        'gs1_company_prefix',
        'gs1_application_identifiers',
        'is_primary',
        'is_active',
        'format_config',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'technology' => 'string',
        'format' => 'string',
        'gs1_application_identifiers' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'format_config' => 'array',
        'metadata' => 'array',
    ];
}
