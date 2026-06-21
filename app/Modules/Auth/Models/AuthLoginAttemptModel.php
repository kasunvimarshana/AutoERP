<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthLoginAttemptModel extends CoreModel
{
    protected $table = 'auth_login_attempts';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'client_id',
        'identity_id',
        'user_id',
        'login_identifier',
        'was_successful',
        'failure_code',
        'ip_address',
        'user_agent',
        'attempt_type',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'was_successful' => 'boolean',
            'attempted_at' => 'datetime',
        ]);
    }
}
