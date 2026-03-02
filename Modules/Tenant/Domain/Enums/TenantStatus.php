<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Trial = 'trial';
}
