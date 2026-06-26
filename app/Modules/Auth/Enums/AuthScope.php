<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum AuthScope: string
{
    case TENANT = 'tenant';
    case PLATFORM = 'platform';
}
