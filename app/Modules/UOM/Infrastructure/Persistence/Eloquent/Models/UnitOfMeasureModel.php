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
            'is_base' => 'boolean',
        ]);
    }
}