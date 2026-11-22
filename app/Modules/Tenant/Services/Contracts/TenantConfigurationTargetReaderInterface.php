<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Services\Contracts;

use Modules\Tenant\Data\TenantConfigurationTarget;

interface TenantConfigurationTargetReaderInterface
{
    public function find(int $tenantId): ?TenantConfigurationTarget;

    public function count(): int;
}
