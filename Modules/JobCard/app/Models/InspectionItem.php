<?php

declare(strict_types=1);

namespace Modules\JobCard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InspectionItem Model
 *
 * Represents an inspection item in a vehicle service job
 *
 * @property int $id
 * @property int $job_card_id
 * @property string $item_type
 * @property string $item_name
 * @property string $condition
 * @property string|null $notes
 * @property array|null $photos
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InspectionItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_card_id',
        'item_type',
        'item_name',
        'condition',
        'notes',
        'photos',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the job card that owns the inspection item.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Scope to filter by item type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope to filter by condition.
     */
    public function scopeOfCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Scope to filter items needing attention.
     */
    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('condition', ['poor', 'needs_replacement']);
    }
}
