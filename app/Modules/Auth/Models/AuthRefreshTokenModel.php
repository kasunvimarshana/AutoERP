<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthRefreshTokenModel extends TenantOwnedModel
{
    protected $table = 'auth_refresh_tokens';
    protected $fillable = [
        'tenant_id', 'access_token_id', 'parent_refresh_token_id', 'family_id', 'session_id',
        'user_id', 'client_id', 'refresh_key', 'refresh_digest', 'status', 'issued_at',
        'expires_at', 'rotated_at', 'revoked_at', 'revocation_reason', 'row_version',
    ];
    protected $hidden = ['refresh_digest'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'issued_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
            'rotated_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
        ]);
    }
}
