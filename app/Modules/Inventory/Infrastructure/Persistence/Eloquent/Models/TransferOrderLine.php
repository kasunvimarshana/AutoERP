<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class TransferOrderLine extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'transfer_order_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'requested_qty' => 'decimal:4',
            'shipped_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\TransferOrder',
            'transfer_order_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item',
            'item_id'
        );
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant',
            'variant_id'
        );
    }
}
