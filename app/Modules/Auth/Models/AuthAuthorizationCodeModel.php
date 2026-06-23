<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class AuthAuthorizationCodeModel extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'auth_authorization_codes';

    protected $fillable = [
        'row_version',
        'tenant_id',
        'organization_unit_id',
        'metadata',
        'provider_id',
        'client_id',
        'identity_id',
        'session_id',
        'user_id',
        'code_key',
        'code_hash',
        'scopes',
        'code_challenge',
        'code_challenge_method',
        'redirect_uri',
        'status',
        'issued_at',
        'expires_at',
        'consumed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'scopes' => 'array',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ]);
    }
}
