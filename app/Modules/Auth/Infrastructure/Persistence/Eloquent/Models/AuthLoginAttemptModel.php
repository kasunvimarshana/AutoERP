<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AuthLoginAttemptModel extends CoreModel
{
    protected $table = 'auth_login_attempts';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'was_successful' => 'boolean',
            'attempted_at' => 'datetime',
        ]);
    }
}
