<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

final class AuthPlatformOperatorPasswordCredentialModel extends CoreModel
{
    protected $table = 'auth_platform_operator_password_credentials';
    protected $fillable = [
        'platform_operator_id', 'password_hash', 'status', 'changed_at',
        'revoked_at', 'row_version',
    ];
    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'platform_operator_id' => 'integer', 'changed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ]);
    }
}
