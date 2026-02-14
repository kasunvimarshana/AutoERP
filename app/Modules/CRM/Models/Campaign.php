<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Campaign Model
 *
 * Represents marketing campaigns in the CRM
 */
class Campaign extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'status',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'expected_revenue',
        'actual_revenue',
        'description',
        'target_audience',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'expected_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the campaign
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Calculate ROI for the campaign
     */
    public function calculateROI(): ?float
    {
        if (! $this->actual_cost || $this->actual_cost == 0) {
            return null;
        }

        return (($this->actual_revenue - $this->actual_cost) / $this->actual_cost) * 100;
    }
}
