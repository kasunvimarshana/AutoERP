<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Domain\Contracts\TenantConfigInterface;

interface TenantConfigManagerInterface
{
    /**
     * Apply a tenant's full configuration to the running Laravel application.
     */
    public function apply(TenantConfigInterface $config): void;
}
