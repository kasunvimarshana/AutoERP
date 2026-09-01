<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Enums\ItemPriceType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryBatchPriceRevision extends TenantOwnedModel
{
    protected $table = 'inventory_batch_price_revisions';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Batch price revisions are immutable. Use the supersede command.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Batch price revisions are immutable and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'batch_id' => 'integer',
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

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
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
