<?php

declare(strict_types=1);

namespace Modules\UOM\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

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
            'is_active' => 'boolean',
        ]);
    }
}
