<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use App\Modules\MasterData\Models\Currency;

class Lead extends Model
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
        'code',
        'title',
        'type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'company_name',
        'designation',
        'industry',
        'company_size',
        'website',
        'source',
        'source_details',
        'status',
        'priority',
        'rating',
        'estimated_value',
        'currency_id',
        'probability',
        'expected_close_date',
        'assigned_to',
        'stage',
        'is_converted',
        'converted_customer_id',
        'converted_at',
        'converted_by',
        'description',
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
        'company_size' => 'integer',
        'rating' => 'integer',
        'estimated_value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'is_converted' => 'boolean',
        'converted_at' => 'datetime',
        'tags' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the lead.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the branch that owns the lead.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the currency for the lead.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user assigned to this lead.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the converted customer.
     */
    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    /**
     * Get the user who converted this lead.
     */
    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    /**
     * Get the user who created the lead.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the lead.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the lead's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Check if lead is converted.
     */
    public function isConverted(): bool
    {
        return $this->is_converted;
    }

    /**
     * Check if lead is qualified.
     */
    public function isQualified(): bool
    {
        return in_array($this->status, ['qualified', 'proposal', 'negotiation']);
    }

    /**
     * Check if lead is won.
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Check if lead is lost.
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if lead is unqualified.
     */
    public function isUnqualified(): bool
    {
        return $this->status === 'unqualified';
    }

    /**
     * Check if lead is business type.
     */
    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }

    /**
     * Check if lead is individual type.
     */
    public function isIndividual(): bool
    {
        return $this->type === 'individual';
    }

    /**
     * Calculate expected revenue based on probability.
     */
    public function getExpectedRevenue(): float
    {
        if (!$this->estimated_value || !$this->probability) {
            return 0;
        }

        return (float) $this->estimated_value * ($this->probability / 100);
    }
}
