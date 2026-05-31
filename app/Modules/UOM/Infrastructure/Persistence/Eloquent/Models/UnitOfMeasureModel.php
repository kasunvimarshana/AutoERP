<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class UnitOfMeasureModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'unit_of_measures';

    protected $guarded = ['id'];

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
            'usable_for_purchase' => 'boolean',
            'usable_for_sales' => 'boolean',
            'usable_for_inventory' => 'boolean',
            'usable_for_service' => 'boolean',
            'usable_for_rental' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
