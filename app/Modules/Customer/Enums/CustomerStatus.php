<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case OnHold = 'on_hold';
    case Blacklisted = 'blacklisted';
    case PendingApproval = 'pending_approval';
}
