<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\ReferenceData\Models\CurrencyModel;

final class TenantModel extends CoreModel
{
    protected $table = 'tenants';

    protected $fillable = [
        'uuid', 'code', 'name', 'slug', 'logo_path', 'cross_org_transactions',
        'tenant_plan_id', 'base_currency_id', 'status', 'status_reason',
        'activated_at', 'suspended_at', 'archived_at', 'trial_ends_at',
        'subscription_ends_at', 'metadata', 'row_version', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_plan_id' => 'integer',
            'base_currency_id' => 'integer',
            'cross_org_transactions' => 'boolean',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlanModel::class, 'tenant_plan_id');
    }

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'base_currency_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocumentModel::class, 'tenant_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomainModel::class, 'tenant_id');
    }
}
