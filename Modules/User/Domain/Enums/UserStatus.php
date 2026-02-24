<?php
namespace Modules\User\Domain\Enums;
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingVerification = 'pending_verification';
}
