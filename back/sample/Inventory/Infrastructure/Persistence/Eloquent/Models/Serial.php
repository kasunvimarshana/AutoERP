<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Domain\Enums\SerialStatus;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Serial extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'serials';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => SerialStatus::class,
            'warranty_expiry' => 'date',
            'manufacture_date' => 'date',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item', 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant', 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\Batch', 'batch_id');
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo('Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\WarehouseLocation', 'current_location_id');
    }
}
