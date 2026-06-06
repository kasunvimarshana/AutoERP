<?php

declare(strict_types=1);

namespace Modules\UOM\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;

final class UnitOfMeasureModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'unit_of_measures';

    protected $guarded = ['id'];

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UomConversionModel::class, 'from_uom_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UomConversionModel::class, 'to_uom_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'decimal_precision' => 'integer',
            'allow_fractional_quantity' => 'boolean',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
