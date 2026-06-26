<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthSessionModel extends TenantOwnedModel
{
    protected $table = 'auth_sessions';
    protected $fillable = [
        'public_id', 'tenant_id', 'organization_unit_id', 'provider_id', 'identity_id',
        'user_id', 'status', 'ip_address', 'user_agent', 'device_name',
        'authenticated_at', 'last_activity_at', 'expires_at', 'revoked_at',
        'revocation_reason', 'row_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'authenticated_at' => 'immutable_datetime', 'last_activity_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
        ]);
    }
}
