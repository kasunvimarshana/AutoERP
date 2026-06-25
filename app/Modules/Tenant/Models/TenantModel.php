<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use LogicException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\CoreModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Services\TenantLifecycleState;

final class TenantModel extends CoreModel
{
    protected $table = 'tenants';

    protected $fillable = [
        'uuid', 'code', 'name', 'slug', 'logo_path',
        'base_currency_id', 'status', 'status_reason', 'status_changed_at',
        'activated_at', 'suspended_at', 'archived_at',
        'row_version', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $tenant): void {
            TenantLifecycleState::assertValid($tenant->getAttributes());
        });
        static::deleting(static function (): never {
            throw new LogicException('Tenants are archived, never hard deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'base_currency_id' => 'integer',
            'status_changed_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
        ]);
    }

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'base_currency_id');
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(TenantCurrentSubscriptionModel::class, 'tenant_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscriptionModel::class, 'tenant_id');
    }

    public function onboardingState(): HasOne
    {
        return $this->hasOne(TenantOnboardingStateModel::class, 'tenant_id');
    }

    public function primaryDomainAssignment(): HasOne
    {
        return $this->hasOne(TenantPrimaryDomainModel::class, 'tenant_id');
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
