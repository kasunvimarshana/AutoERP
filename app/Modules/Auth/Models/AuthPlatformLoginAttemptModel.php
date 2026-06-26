<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformLoginAttemptModel extends CoreModel
{
    protected $table = 'auth_platform_login_attempts';
    protected $fillable = [
        'platform_operator_id', 'login_identifier_hash', 'was_successful',
        'failure_code', 'ip_address', 'user_agent', 'attempted_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'was_successful' => 'boolean',
            'attempted_at' => 'immutable_datetime',
        ]);
    }
}
