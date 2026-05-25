<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ItemVariantAttributeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'item_variant_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'item_id' => 'integer',
            'attribute_id' => 'integer',
            'is_required' => 'boolean',
            'display_order' => 'integer',
        ]);
    }
}
