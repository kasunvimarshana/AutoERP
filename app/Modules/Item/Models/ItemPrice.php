<?php

declare(strict_types=1);

namespace Modules\Item\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Item\Enums\ItemPriceType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemPrice extends CoreModel
{
    protected $table = 'item_prices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'price_type' => ItemPriceType::class,
            'currency_id' => 'integer',
            'uom_id' => 'integer',
            'amount' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
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

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
