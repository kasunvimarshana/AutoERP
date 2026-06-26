<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformRefreshTokenModel extends CoreModel
{
    protected $table = 'auth_platform_refresh_tokens';
    protected $fillable = [
        'access_token_id', 'parent_refresh_token_id', 'family_id', 'platform_session_id',
        'platform_operator_id', 'refresh_key', 'refresh_digest', 'status', 'issued_at',
        'expires_at', 'rotated_at', 'revoked_at', 'revocation_reason', 'row_version',
    ];
    protected $hidden = ['refresh_digest'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'rotated_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ]);
    }
}
