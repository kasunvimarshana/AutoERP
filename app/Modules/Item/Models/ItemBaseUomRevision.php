<?php

declare(strict_types=1);

namespace Modules\Item\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Enums\ItemBaseUomRevisionStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\User\Models\UserModel;

final class ItemBaseUomRevision extends TenantOwnedModel
{
    protected $table = 'item_base_uom_revisions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'old_base_uom_id' => 'integer',
            'new_base_uom_id' => 'integer',
            'conversion_factor' => 'decimal:6',
            'effective_at' => 'datetime',
            'status' => ItemBaseUomRevisionStatus::class,
            'validation_summary' => 'array',
            'created_by' => 'integer',
            'applied_by' => 'integer',
            'applied_at' => 'datetime',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function oldBaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'old_base_uom_id');
    }

    public function newBaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'new_base_uom_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    public function applier(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'applied_by');
    }
}
