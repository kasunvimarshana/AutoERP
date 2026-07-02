<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tenant\Services\Onboarding\TenantOnboardingStatePolicy;

final class TenantOnboardingStateModel extends TenantOwnedModel
{
    protected $table = 'tenant_onboarding_states';
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'tenant_id',
        'status',
        'operation_id',
        'operation_started_at',
        'operation_lease_expires_at',
        'initial_admin_email',
        'root_organization_unit_id',
        'super_admin_role_id',
        'administrator_user_id',
        'failed_step',
        'last_error_code',
        'last_error_message',
        'correlation_id',
        'provisioned_at',
        'completed_at',
        'created_by',
        'updated_by',
        'row_version',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $state): void {
            TenantOnboardingStatePolicy::assertValid($state->getAttributes());
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'root_organization_unit_id' => 'integer',
            'super_admin_role_id' => 'integer',
            'administrator_user_id' => 'integer',
            'operation_started_at' => 'datetime',
            'operation_lease_expires_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TenantOnboardingStepModel::class, 'tenant_id', 'tenant_id')
            ->orderBy('id');
    }
}
