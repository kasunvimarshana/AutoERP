<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ItemVariantModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'item_variants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'item_id' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
        ]);
    }
}
