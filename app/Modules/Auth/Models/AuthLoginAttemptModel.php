<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Modules\Core\Models\CoreModel;

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
