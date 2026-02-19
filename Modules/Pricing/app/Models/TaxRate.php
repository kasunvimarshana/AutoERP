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
 * TaxRate Model
 *
 * Represents tax rates by jurisdiction and product category
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $rate
 * @property string|null $jurisdiction
 * @property string|null $product_category
 * @property bool $is_compound
 * @property bool $is_active
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $effective_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TaxRate extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    protected $table = 'tax_rates';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'rate',
        'jurisdiction',
        'product_category',
        'is_compound',
        'is_active',
        'priority',
        'effective_date',
        'expiry_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_compound' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'effective_date' => 'datetime',
            'expiry_date' => 'datetime',
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
     * Scope for active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for jurisdiction
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Scope for product category
     */
    public function scopeForProductCategory($query, string $category)
    {
        return $query->where('product_category', $category);
    }

    /**
     * Scope for effective tax rates
     */
    public function scopeEffectiveAt($query, \Illuminate\Support\Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_date')
                ->orWhere('effective_date', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', $date);
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
     * Calculate tax amount
     */
    public function calculateTax(string $amount, bool $inclusive = false): string
    {
        if ($inclusive) {
            // Tax is already included in the amount
            $divisor = bcadd('100', $this->rate, 4);

            return bcdiv(bcmul($amount, $this->rate, 4), $divisor, 2);
        }

        // Tax needs to be added to the amount
        return bcmul($amount, bcdiv($this->rate, '100', 4), 2);
    }

    /**
     * Check if tax rate is currently effective
     */
    public function isEffectiveNow(): bool
    {
        $now = now();

        if ($this->effective_date && $this->effective_date->isAfter($now)) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isBefore($now)) {
            return false;
        }

        return true;
    }
}
