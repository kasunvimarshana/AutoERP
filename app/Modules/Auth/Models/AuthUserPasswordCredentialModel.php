<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\TenantOwnedModel;

final class AuthUserPasswordCredentialModel extends TenantOwnedModel
{
    protected $table = 'auth_user_password_credentials';
    protected $fillable = [
        'tenant_id', 'user_id', 'password_hash', 'status', 'changed_at',
        'revoked_at', 'row_version',
    ];
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'changed_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
        ]);
    }
}
