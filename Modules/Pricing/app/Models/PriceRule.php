<?php

declare(strict_types=1);

namespace Modules\Pricing\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Models\Branch;

/**
 * PriceRule Model
 *
 * Represents dynamic pricing rules with conditions and actions
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int $priority
 * @property array|null $conditions
 * @property array|null $actions
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PriceRule extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    protected $table = 'price_rules';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'is_active',
        'priority',
        'conditions',
        'actions',
        'start_date',
        'end_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'conditions' => 'array',
            'actions' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid rules at specific date
     */
    public function scopeValidAt($query, \Illuminate\Support\Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date);
        });
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if rule is currently valid
     */
    public function isValidNow(): bool
    {
        $now = now();

        if ($this->start_date && $this->start_date->isAfter($now)) {
            return false;
        }

        if ($this->end_date && $this->end_date->isBefore($now)) {
            return false;
        }

        return true;
    }
}
