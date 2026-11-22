<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Services;

final class PlatformRefreshTokenCookie extends RefreshTokenCookie
{
    public function __construct()
    {
        parent::__construct('module-auth.cookies.platform_refresh');
    }
}
