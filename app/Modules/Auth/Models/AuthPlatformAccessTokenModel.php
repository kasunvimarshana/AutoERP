<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformAccessTokenModel extends CoreModel
{
    protected $table = 'auth_platform_access_tokens';
    protected $fillable = [
        'platform_session_id', 'platform_operator_id', 'token_key', 'token_digest',
        'scopes', 'grant_type', 'status', 'issued_at', 'expires_at', 'revoked_at',
        'revocation_reason', 'row_version',
    ];
    protected $hidden = ['token_digest'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'scopes' => 'array',
            'issued_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ]);
    }
}
