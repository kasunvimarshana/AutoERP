<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum ProviderStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
