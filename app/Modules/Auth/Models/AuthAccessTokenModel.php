<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthAccessTokenModel extends TenantOwnedModel
{
    protected $table = 'auth_access_tokens';
    protected $fillable = [
        'tenant_id', 'session_id', 'user_id', 'client_id', 'token_key', 'token_digest',
        'scopes', 'grant_type', 'status', 'issued_at', 'expires_at', 'revoked_at',
        'revocation_reason', 'row_version',
    ];
    protected $hidden = ['token_digest'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scopes' => 'array', 'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
        ]);
    }
}
