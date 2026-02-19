<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableContract;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Traits\TenantScoped;

class Subscription extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'plan_id',
        'user_id',
        'subscription_code',
        'status',
        'amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'suspended_at',
        'metadata',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function successfulPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class)->where('status', 'succeeded');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trial;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled;
    }

    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::Expired;
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function getAuditableAttributes(): array
    {
        return [
            'subscription_code',
            'status',
            'amount',
            'total_amount',
            'current_period_start',
            'current_period_end',
        ];
    }
}
