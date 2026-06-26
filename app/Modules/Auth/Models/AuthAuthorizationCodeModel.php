<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthAuthorizationCodeModel extends TenantOwnedModel
{
    protected $table = 'auth_authorization_codes';
    protected $fillable = [
        'tenant_id', 'client_id', 'session_id', 'user_id', 'code_key', 'code_digest',
        'scopes', 'code_challenge', 'redirect_uri', 'status', 'issued_at',
        'expires_at', 'consumed_at', 'revoked_at', 'row_version',
    ];
    protected $hidden = ['code_digest', 'code_challenge'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scopes' => 'array', 'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'consumed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ]);
    }
}
