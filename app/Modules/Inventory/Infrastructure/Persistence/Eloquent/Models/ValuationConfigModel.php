<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $org_unit_id
 * @property int|null $warehouse_id
 * @property int|null $product_id
 * @property string|null $transaction_type
 * @property string $valuation_method
 * @property string $allocation_strategy
 * @property bool $is_active
 * @property array|null $metadata
 */
class ValuationConfigModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'valuation_configs';

    protected $fillable = [
        'tenant_id',
            'org_unit_id',
            'row_version',
        'warehouse_id',
        'product_id',
        'transaction_type',
        'valuation_method',
        'allocation_strategy',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'warehouse_id' => 'integer',
        'product_id' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
