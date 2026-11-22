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

final class TenantRefreshTokenCookie extends RefreshTokenCookie
{
    public function __construct()
    {
        parent::__construct('module-auth.cookies.tenant_refresh');
    }
}
