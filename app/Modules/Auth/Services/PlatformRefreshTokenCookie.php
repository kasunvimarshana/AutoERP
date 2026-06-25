<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

final class PlatformRefreshTokenCookie extends RefreshTokenCookie
{
    public function __construct()
    {
        parent::__construct('module-auth.cookies.platform_refresh');
    }
}
