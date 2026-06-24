<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Core\Models\TenantOwnedModel;

final class TenantOnboardingStateModel extends TenantOwnedModel
{
    protected $table = 'tenant_onboarding_states';

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'tenant_id',
        'status',
        'initial_admin_email',
        'root_organization_unit_id',
        'super_admin_role_id',
        'invitation_id',
        'completed_steps',
        'last_error',
        'provisioned_at',
        'completed_at',
        'created_by',
        'updated_by',
        'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'root_organization_unit_id' => 'integer',
            'super_admin_role_id' => 'integer',
            'invitation_id' => 'integer',
            'completed_steps' => 'array',
            'provisioned_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }
}
