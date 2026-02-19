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
use Modules\Billing\Enums\BillingInterval;
use Modules\Billing\Enums\PlanType;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Traits\TenantScoped;

class Plan extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'billing_plans';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'price',
        'interval',
        'interval_count',
        'trial_days',
        'features',
        'limits',
        'user_limit',
        'storage_limit_gb',
        'is_active',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'type' => PlanType::class,
        'price' => 'decimal:2',
        'interval' => BillingInterval::class,
        'interval_count' => 'integer',
        'trial_days' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'user_limit' => 'integer',
        'storage_limit_gb' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    public function getAuditableAttributes(): array
    {
        return [
            'code',
            'name',
            'type',
            'price',
            'interval',
            'is_active',
        ];
    }
}
