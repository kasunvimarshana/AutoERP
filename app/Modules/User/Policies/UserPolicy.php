<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\User\Constants\UserPermission;
use Modules\User\Models\UserModel;
use Modules\User\Services\UserAccessResolver;

final class UserPolicy
{
    public function __construct(private readonly UserAccessResolver $access) {}

    public function viewAny(UserModel $actor): bool
    {
        return $this->can($actor, UserPermission::USERS_VIEW);
    }

    public function view(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && ((int) $actor->getKey() === (int) $subject->getKey() || $this->can($actor, UserPermission::USERS_VIEW));
    }

    public function create(UserModel $actor): bool
    {
        return $this->can($actor, UserPermission::USERS_CREATE);
    }

    public function update(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject) && $this->can($actor, UserPermission::USERS_UPDATE);
    }

    public function delete(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && (int) $actor->getKey() !== (int) $subject->getKey()
            && $this->can($actor, UserPermission::USERS_DELETE);
    }

    public function changeStatus(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && (int) $actor->getKey() !== (int) $subject->getKey()
            && ($this->can($actor, UserPermission::USERS_ACTIVATE)
                || $this->can($actor, UserPermission::USERS_DEACTIVATE));
    }

    public function assignRoles(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject) && $this->can($actor, UserPermission::USERS_ASSIGN_ROLES);
    }

    public function manageOrganizationAccess(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && $this->can($actor, UserPermission::USERS_MANAGE_ORGANIZATION_ACCESS);
    }

    public function assignPermissions(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && $this->can($actor, UserPermission::USERS_ASSIGN_PERMISSIONS);
    }

    public function viewDocuments(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && ((int) $actor->getKey() === (int) $subject->getKey()
                || $this->can($actor, UserPermission::USER_DOCUMENTS_VIEW));
    }

    public function manageDocuments(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && ((int) $actor->getKey() === (int) $subject->getKey()
                || $this->can($actor, UserPermission::USER_DOCUMENTS_MANAGE));
    }

    public function viewDevices(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && ((int) $actor->getKey() === (int) $subject->getKey()
                || $this->can($actor, UserPermission::USER_DEVICES_VIEW));
    }

    public function manageDevices(UserModel $actor, UserModel $subject): bool
    {
        return $this->sameTenant($actor, $subject)
            && ((int) $actor->getKey() === (int) $subject->getKey()
                || $this->can($actor, UserPermission::USER_DEVICES_MANAGE));
    }

    private function can(UserModel $actor, string $permission): bool
    {
        $tenantId = (int) $actor->getAttribute('tenant_id');

        return $tenantId > 0 && $this->access->can((int) $actor->getKey(), $tenantId, $permission);
    }

    private function sameTenant(UserModel $actor, UserModel $subject): bool
    {
        return (int) $actor->getAttribute('tenant_id') > 0
            && (int) $actor->getAttribute('tenant_id') === (int) $subject->getAttribute('tenant_id');
    }
}
