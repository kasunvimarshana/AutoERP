<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\UOM\Models\UnitOfMeasureModel;

final class VehicleServiceLegacyHistoryItem extends TenantOwnedModel
{
    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $table = 'vehicle_service_legacy_history_items';

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new LogicException('Imported vehicle service history items are immutable.'));
        self::deleting(static fn (): never => throw new LogicException('Imported vehicle service history items cannot be deleted.'));
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'legacy_history_id' => 'integer',
            'line_number' => 'integer',
            'item_id' => 'integer',
            'uom_id' => 'integer',
            'quantity' => 'decimal:6',
            'source_payload' => 'array',
            'imported_at' => 'datetime',
        ]);
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceLegacyHistory::class, 'legacy_history_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id')->withTrashed();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id')->withTrashed();
    }
}
