<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\User\Models\UserModel;

final class AuthClientPolicy
{
    public function viewAny(?UserModel $user = null): bool
    {
        return true;
    }

    public function create(?UserModel $user = null): bool
    {
        return true;
    }
}
