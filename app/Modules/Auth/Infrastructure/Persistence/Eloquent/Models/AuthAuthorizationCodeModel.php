<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AuthAuthorizationCodeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'auth_authorization_codes';

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
