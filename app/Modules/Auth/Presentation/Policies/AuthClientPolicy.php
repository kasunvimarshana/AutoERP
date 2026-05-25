<?php

declare(strict_types=1);

namespace Modules\Auth\Presentation\Policies;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

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
