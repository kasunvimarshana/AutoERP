<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Core\Models\TenantOwnedModel;

final class TenantOnboardingStepModel extends TenantOwnedModel
{
    protected $table = 'tenant_onboarding_steps';

    protected $fillable = [
        'tenant_id',
        'step',
        'owner_module',
        'status',
        'attempt_count',
        'operation_id',
        'started_at',
        'completed_at',
        'error_code',
        'error_message',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'attempt_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ]);
    }
}
