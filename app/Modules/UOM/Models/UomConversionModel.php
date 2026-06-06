<?php

declare(strict_types=1);

namespace Modules\UOM\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class UomConversionModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'uom_conversions';

    protected $guarded = ['id'];

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'from_uom_id');
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'to_uom_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'from_uom_id' => 'integer',
            'to_uom_id' => 'integer',
            'conversion_factor' => 'decimal:6',
            'is_active' => 'boolean',
        ]);
    }
}
