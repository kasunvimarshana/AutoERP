<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lead Model
 *
 * Represents potential customers in the CRM pipeline
 */
class Lead extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'assigned_to',
        'name',
        'email',
        'phone',
        'company',
        'title',
        'source',
        'status',
        'rating',
        'estimated_value',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the lead
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the user assigned to this lead
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
