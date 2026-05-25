<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Policies;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

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
