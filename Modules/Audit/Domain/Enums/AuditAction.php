<?php
namespace Modules\Audit\Domain\Enums;
enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Viewed = 'viewed';
    case Exported = 'exported';
    case Login = 'login';
    case Logout = 'logout';
}
