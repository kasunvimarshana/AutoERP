<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Enums;

enum GrantType: string
{
    case TENANT_PASSWORD = 'tenant_password';
    case PLATFORM_PASSWORD = 'platform_password';
    case REFRESH_TOKEN = 'refresh_token';
    case AUTHORIZATION_CODE = 'authorization_code';
}
