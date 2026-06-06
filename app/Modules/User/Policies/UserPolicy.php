<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\User\Models\UserModel;

final class UserPolicy
{
    public function viewAny(UserModel $actor): bool
    {
        return true;
    }

    public function view(UserModel $actor, UserModel $record): bool
    {
        return true;
    }

    public function create(UserModel $actor): bool
    {
        return true;
    }

    public function update(UserModel $actor, UserModel $record): bool
    {
        return true;
    }

    public function delete(UserModel $actor, UserModel $record): bool
    {
        return true;
    }
}
