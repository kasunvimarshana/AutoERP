<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model
 *
 * Represents a customer in the vehicle service center system.
 * Supports multi-tenancy and multi-branch operations.
 *
 * @property int $id
 * @property string $customer_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $notes
 * @property string $status
 * @property string $customer_type
 * @property string|null $company_name
 * @property string|null $tax_id
 * @property bool $receive_notifications
 * @property bool $receive_marketing
 * @property \Carbon\Carbon|null $last_service_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Customer extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Customer\Database\Factories\CustomerFactory
    {
        return \Modules\Customer\Database\Factories\CustomerFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'status',
        'customer_type',
        'company_name',
        'tax_id',
        'receive_notifications',
        'receive_marketing',
        'last_service_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'receive_notifications' => 'boolean',
            'receive_marketing' => 'boolean',
            'last_service_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the vehicles owned by the customer.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the service records for the customer.
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(VehicleServiceRecord::class);
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the customer's display name (full name or company name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->customer_type === 'business' && $this->company_name
            ? $this->company_name
            : $this->full_name;
    }

    /**
     * Scope to filter active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by customer type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Generate a unique customer number.
     */
    public static function generateCustomerNumber(): string
    {
        $prefix = 'CUST';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
