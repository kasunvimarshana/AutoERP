<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

final class TenantRefreshTokenCookie extends RefreshTokenCookie
{
    public function __construct()
    {
        parent::__construct('module-auth.cookies.tenant_refresh');
    }
}
