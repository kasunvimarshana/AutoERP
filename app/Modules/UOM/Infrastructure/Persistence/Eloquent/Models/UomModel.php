<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class UomModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'unit_of_measures';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'decimal_precision' => 'integer',
            'is_base' => 'boolean',
            'row_version' => 'integer',
        ];
    }
}
