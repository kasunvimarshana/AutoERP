<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthClientModel extends TenantOwnedModel
{
    protected $table = 'auth_clients';
    protected $fillable = [
        'tenant_id', 'client_key', 'client_name', 'client_secret_hash', 'status',
        'allowed_grant_types', 'allowed_scopes', 'redirect_uris', 'is_confidential',
        'is_first_party', 'secret_rotated_at', 'expires_at', 'row_version',
    ];
    protected $hidden = ['client_secret_hash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'allowed_grant_types' => 'array', 'allowed_scopes' => 'array',
            'redirect_uris' => 'array', 'is_confidential' => 'boolean',
            'is_first_party' => 'boolean', 'secret_rotated_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ]);
    }
}
