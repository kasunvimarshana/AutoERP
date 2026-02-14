<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Opportunity Model
 *
 * Represents sales opportunities in the CRM pipeline
 */
class Opportunity extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'customer_id',
        'assigned_to',
        'title',
        'description',
        'value',
        'probability',
        'stage',
        'expected_close_date',
        'actual_close_date',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the opportunity
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the lead associated with this opportunity
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the customer associated with this opportunity
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user assigned to this opportunity
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Calculate weighted value based on probability
     */
    public function getWeightedValue(): float
    {
        return (float) $this->value * ($this->probability / 100);
    }
}
