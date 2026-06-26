<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthLoginAttemptModel extends TenantOwnedModel
{
    protected $table = 'auth_login_attempts';
    protected $fillable = [
        'tenant_id', 'user_id', 'login_identifier_hash', 'was_successful',
        'failure_code', 'ip_address', 'user_agent', 'attempted_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'was_successful' => 'boolean', 'attempted_at' => 'immutable_datetime',
        ]);
    }
}
