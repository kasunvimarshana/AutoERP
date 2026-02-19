<?php

declare(strict_types=1);

namespace Modules\Pricing\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Models\Customer;
use Modules\Organization\Models\Branch;
use Modules\Pricing\Database\Factories\PriceListFactory;

/**
 * PriceList Model
 *
 * Represents a pricing list with customer/location-specific pricing
 *
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $status
 * @property string $currency_code
 * @property bool $is_default
 * @property int $priority
 * @property int|null $customer_id
 * @property string|null $location_code
 * @property string|null $customer_group
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PriceList extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    protected $table = 'price_lists';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'status',
        'currency_code',
        'is_default',
        'priority',
        'customer_id',
        'location_code',
        'customer_group',
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
            'is_default' => 'boolean',
            'priority' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the branch that owns the price list
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the customer associated with the price list
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the price list items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    /**
     * Scope for active price lists
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for default price lists
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for price lists valid at a specific date
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
     * Scope for customer-specific price lists
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for location-specific price lists
     */
    public function scopeForLocation($query, string $locationCode)
    {
        return $query->where('location_code', $locationCode);
    }

    /**
     * Scope for customer group price lists
     */
    public function scopeForCustomerGroup($query, string $group)
    {
        return $query->where('customer_group', $group);
    }

    /**
     * Check if price list is currently valid
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

    /**
     * Check if price list is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PriceListFactory
    {
        return PriceListFactory::new();
    }
}
