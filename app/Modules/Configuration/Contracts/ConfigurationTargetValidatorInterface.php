<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Contracts;

interface ConfigurationTargetValidatorInterface
{
    public function assertTenantReadable(int $tenantId): void;

    public function assertTenantWritable(int $tenantId): void;
}
