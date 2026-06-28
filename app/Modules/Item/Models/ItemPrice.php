<?php

declare(strict_types=1);

namespace Modules\Item\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Enums\ItemPriceType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class ItemPrice extends TenantOwnedModel
{
    protected $table = 'item_prices';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'item_id',
        'item_variant_id',
        'price_type',
        'currency_id',
        'uom_id',
        'amount',
        'effective_from',
        'effective_to',
        'scope_key',
        'lineage_key',
        'revision_no',
        'supersedes_price_id',
        'recorded_from',
        'recorded_to',
        'correction_reason',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Item price revisions are immutable. Use the supersede command.');
        });
        static::deleting(static function (): never {
            throw new LogicException('Item price revisions are immutable and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
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
            'revision_no' => 'integer',
            'supersedes_price_id' => 'integer',
            'recorded_from' => 'datetime',
            'recorded_to' => 'datetime',
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

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_price_id');
    }
}
