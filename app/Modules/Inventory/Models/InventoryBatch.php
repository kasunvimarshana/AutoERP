<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Enums\BatchStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;

final class InventoryBatch extends CoreModel
{
    use SoftDeletes;

    protected $table = 'inventory_batches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'status' => BatchStatus::class,
            'metadata' => 'array',
        ]);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(InventorySerialNumber::class, 'batch_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class, 'batch_id');
    }
}
