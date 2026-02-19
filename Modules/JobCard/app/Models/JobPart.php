<?php

declare(strict_types=1);

namespace Modules\JobCard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JobPart Model
 *
 * Represents a part used in a job card
 *
 * @property int $id
 * @property int $job_card_id
 * @property int|null $inventory_item_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_price
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class JobPart extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_card_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the job card that owns the part.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate total price based on quantity and unit price.
     */
    public function calculateTotal(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
    }
}
