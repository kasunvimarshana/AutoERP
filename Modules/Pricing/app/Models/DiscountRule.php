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
 * DiscountRule Model
 *
 * Represents discount rules with conditions
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $type
 * @property string|null $value
 * @property string|null $max_discount_amount
 * @property string|null $min_purchase_amount
 * @property bool $is_active
 * @property int $priority
 * @property array|null $conditions
 * @property array|null $applicable_products
 * @property array|null $applicable_categories
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class DiscountRule extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    protected $table = 'discount_rules';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'type',
        'value',
        'max_discount_amount',
        'min_purchase_amount',
        'is_active',
        'priority',
        'conditions',
        'applicable_products',
        'applicable_categories',
        'usage_limit',
        'usage_count',
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
            'value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'conditions' => 'array',
            'applicable_products' => 'array',
            'applicable_categories' => 'array',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
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
     * Scope for valid rules
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
     * Scope by discount code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Check if rule has usage remaining
     */
    public function hasUsageRemaining(): bool
    {
        if (! $this->usage_limit) {
            return true;
        }

        return $this->usage_count < $this->usage_limit;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
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
