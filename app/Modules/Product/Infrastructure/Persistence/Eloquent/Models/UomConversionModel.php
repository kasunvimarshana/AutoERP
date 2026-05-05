<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class UomConversionModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'from_uom_id',
        'to_uom_id',
        'factor',
        'is_bidirectional',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'from_uom_id' => 'integer',
        'to_uom_id' => 'integer',
        'factor' => 'decimal:10',
        'is_bidirectional' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'from_uom_id');
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'to_uom_id');
    }
}
