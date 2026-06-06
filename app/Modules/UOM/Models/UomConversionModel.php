<?php

declare(strict_types=1);

namespace Modules\UOM\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class UomConversionModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'uom_conversions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'from_uom_id' => 'integer',
            'to_uom_id' => 'integer',
            'factor' => 'decimal:8',
            'is_bidirectional' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
