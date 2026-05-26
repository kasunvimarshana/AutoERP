<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ResolveConfigurationServiceInterface
{
    public function execute(
        string $key,
        ?int $tenantId = null,
        ?int $organizationUnitId = null,
        mixed $defaultValue = null,
    ): Result;
}
