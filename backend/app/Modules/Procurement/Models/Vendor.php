<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Modules\MasterData\Models\Currency;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'organization_id',
        'branch_id',
        'type',
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_number',
        'company_name',
        'industry',
        'established_date',
        'credit_limit',
        'payment_terms_days',
        'payment_terms_type',
        'currency_id',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'swift_code',
        'is_active',
        'is_verified',
        'status',
        'rating',
        'vendor_category',
        'source',
        'assigned_to',
        'notes',
        'tags',
        'custom_fields',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'established_date' => 'date',
        'rating' => 'integer',
        'tags' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the vendor.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the branch that owns the vendor.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the currency for the vendor.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user assigned to this vendor.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the contacts for the vendor.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    /**
     * Get the primary contact for the vendor.
     */
    public function primaryContact(): HasMany
    {
        return $this->hasMany(VendorContact::class)
            ->where('is_primary', true);
    }

    /**
     * Get the purchase orders for the vendor.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the user who created the vendor.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the vendor.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if vendor is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Check if vendor is verified.
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if vendor is business type.
     */
    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }

    /**
     * Check if vendor is individual type.
     */
    public function isIndividual(): bool
    {
        return $this->type === 'individual';
    }
}
