<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * StockCount Model
 *
 * Represents physical inventory count headers.
 * Used for reconciling physical counts with system records.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $warehouse_id
 * @property string $count_number
 * @property StockCountStatus $status
 * @property \Carbon\Carbon $count_date
 * @property \Carbon\Carbon|null $scheduled_date
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $reconciled_at
 * @property string|null $counted_by
 * @property string|null $approved_by
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StockCount extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'count_number',
        'status',
        'count_date',
        'scheduled_date',
        'started_at',
        'completed_at',
        'reconciled_at',
        'counted_by',
        'approved_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => StockCountStatus::class,
        'count_date' => 'date',
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Get the warehouse for this stock count.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the count items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    /**
     * Check if count can be modified.
     */
    public function canModify(): bool
    {
        return in_array($this->status, [
            StockCountStatus::PLANNED,
            StockCountStatus::IN_PROGRESS,
        ], true);
    }

    /**
     * Check if count can be started.
     */
    public function canStart(): bool
    {
        return $this->status === StockCountStatus::PLANNED;
    }

    /**
     * Check if count can be completed.
     */
    public function canComplete(): bool
    {
        return $this->status === StockCountStatus::IN_PROGRESS;
    }

    /**
     * Check if count can be reconciled.
     */
    public function canReconcile(): bool
    {
        return $this->status === StockCountStatus::COMPLETED;
    }

    /**
     * Check if count can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            StockCountStatus::PLANNED,
            StockCountStatus::IN_PROGRESS,
        ], true);
    }

    /**
     * Get items with variances.
     */
    public function getItemsWithVariances(): HasMany
    {
        return $this->items()->where(function ($query) {
            $query->whereColumn('counted_quantity', '!=', 'system_quantity')
                ->whereNotNull('counted_quantity');
        });
    }

    /**
     * Get total variance count.
     */
    public function getTotalVarianceCount(): int
    {
        return $this->getItemsWithVariances()->count();
    }

    /**
     * Get total variance value.
     */
    public function getTotalVarianceValue(): string
    {
        $total = '0';

        foreach ($this->items as $item) {
            if ($item->variance !== null && bccomp((string) $item->variance, '0', 6) != 0) {
                $itemValue = bcmul((string) $item->variance, (string) ($item->unit_cost ?? '0'), 6);
                $total = bcadd($total, $itemValue, 6);
            }
        }

        return $total;
    }
}
