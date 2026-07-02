<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformSessionModel extends CoreModel
{
    protected $table = 'auth_platform_sessions';
    protected $fillable = [
        'public_id', 'platform_operator_id', 'status', 'ip_address', 'user_agent',
        'device_name', 'authenticated_at', 'last_activity_at',
        'expires_at', 'revoked_at', 'revocation_reason', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'authenticated_at' => 'immutable_datetime',
            'last_activity_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
        ]);
    }
}
