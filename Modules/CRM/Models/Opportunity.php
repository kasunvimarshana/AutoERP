<?php

declare(strict_types=1);

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable;
use Modules\Auth\Models\User;
use Modules\CRM\Enums\OpportunityStage;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

class Opportunity extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'customer_id',
        'assigned_to',
        'opportunity_code',
        'name',
        'stage',
        'amount',
        'probability',
        'expected_close_date',
        'actual_close_date',
        'lead_source',
        'next_step',
        'description',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'stage' => OpportunityStage::class,
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getExpectedRevenueAttribute(): string
    {
        return bcmul($this->amount, (string) ($this->probability / 100), 2);
    }

    public function isClosed(): bool
    {
        return in_array($this->stage, [
            OpportunityStage::CLOSED_WON,
            OpportunityStage::CLOSED_LOST,
        ]);
    }

    public function isWon(): bool
    {
        return $this->stage === OpportunityStage::CLOSED_WON;
    }
}
