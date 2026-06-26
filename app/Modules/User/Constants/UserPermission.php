<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class UserPermission
{
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';
    public const USERS_ACTIVATE = 'users.activate';
    public const USERS_DEACTIVATE = 'users.deactivate';
    public const USERS_ASSIGN_ROLES = 'users.assign_roles';
    public const USERS_ASSIGN_PERMISSIONS = 'users.assign_permissions';
    public const USERS_MANAGE_ORGANIZATION_ACCESS = 'users.manage_organization_access';
    public const USERS_MANAGE_INVITATIONS = 'users.manage_invitations';
    public const USER_DOCUMENTS_VIEW = 'users.documents.view';
    public const USER_DOCUMENTS_MANAGE = 'users.documents.manage';
    public const USER_DEVICES_VIEW = 'users.devices.view';
    public const USER_DEVICES_MANAGE = 'users.devices.manage';

    public const ROLES_VIEW = 'roles.view';
    public const ROLES_CREATE = 'roles.create';
    public const ROLES_UPDATE = 'roles.update';
    public const ROLES_DELETE = 'roles.delete';
    public const ROLES_ASSIGN_PERMISSIONS = 'roles.assign_permissions';

    public const PERMISSIONS_VIEW = 'permissions.view';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::USERS_VIEW => 'View tenant users and access summaries.',
            self::USERS_CREATE => 'Invite tenant users.',
            self::USERS_UPDATE => 'Update tenant user profile fields.',
            self::USERS_DELETE => 'Archive tenant user accounts when policy allows it.',
            self::USERS_ACTIVATE => 'Reactivate credential-ready tenant user accounts.',
            self::USERS_DEACTIVATE => 'Deactivate or suspend tenant user accounts.',
            self::USERS_ASSIGN_ROLES => 'Assign and remove tenant roles from users.',
            self::USERS_ASSIGN_PERMISSIONS => 'Assign exceptional direct permissions to tenant users.',
            self::USERS_MANAGE_ORGANIZATION_ACCESS => 'Manage user organization-unit access.',
            self::USERS_MANAGE_INVITATIONS => 'Resend or revoke tenant user invitations.',
            self::USER_DOCUMENTS_VIEW => 'View user documents permitted by subject access policy.',
            self::USER_DOCUMENTS_MANAGE => 'Upload, replace, and remove permitted user documents.',
            self::USER_DEVICES_VIEW => 'View registered devices permitted by subject access policy.',
            self::USER_DEVICES_MANAGE => 'Review and revoke registered devices for permitted users.',
            self::ROLES_VIEW => 'View tenant roles and assigned permissions.',
            self::ROLES_CREATE => 'Create tenant roles.',
            self::ROLES_UPDATE => 'Update tenant role definitions.',
            self::ROLES_DELETE => 'Archive unassigned tenant roles.',
            self::ROLES_ASSIGN_PERMISSIONS => 'Assign and remove permissions on tenant roles.',
            self::PERMISSIONS_VIEW => 'View the system-defined permission catalogue.',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_keys(self::descriptions());
    }

    private function __construct() {}
}
