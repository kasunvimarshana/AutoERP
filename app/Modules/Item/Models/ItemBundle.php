<?php

declare(strict_types=1);

namespace Modules\Item\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemBundle extends CoreModel
{
    protected $table = 'item_bundles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'parent_item_id' => 'integer',
            'child_item_id' => 'integer',
            'child_variant_id' => 'integer',
            'quantity' => 'decimal:6',
            'uom_id' => 'integer',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_item_id');
    }

    public function childItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'child_item_id');
    }

    public function childVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'child_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
