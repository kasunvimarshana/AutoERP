<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Viewed = 'viewed';
    case Exported = 'exported';
    case Login = 'login';
    case Logout = 'logout';
    case PasswordChanged = 'password_changed';
    case PermissionChanged = 'permission_changed';
}
