<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

interface ConfigurationTargetValidatorInterface
{
    public function assertTenantReadable(int $tenantId): void;

    public function assertTenantWritable(int $tenantId): void;
}
