<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\TaskStatus;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class PickingTask extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'picking_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function receiptInspection(): BelongsTo
    {
        return $this->belongsTo('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\ReceiptInspection', 'receipt_inspection_id');
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\StockMovement', 'stock_movement_id');
    }
}
