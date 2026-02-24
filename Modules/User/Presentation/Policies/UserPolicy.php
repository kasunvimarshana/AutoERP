<?php
namespace Modules\User\Presentation\Policies;
use Modules\User\Infrastructure\Models\UserModel;
class UserPolicy
{
    public function viewAny(UserModel $authUser): bool { return true; }
    public function view(UserModel $authUser, UserModel $user): bool
    {
        return $authUser->tenant_id === $user->tenant_id;
    }
    public function update(UserModel $authUser, UserModel $user): bool
    {
        return $authUser->tenant_id === $user->tenant_id;
    }
    public function delete(UserModel $authUser, UserModel $user): bool
    {
        return $authUser->tenant_id === $user->tenant_id && $authUser->id !== $user->id;
    }
}
