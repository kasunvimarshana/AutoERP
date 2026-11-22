<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Queue;

interface TenantAwareJobInterface
{
    public function tenantJobContext(): TenantJobContext;
}
