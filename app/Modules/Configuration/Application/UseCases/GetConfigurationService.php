<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Core\Application\Results\Result;

final class GetConfigurationService
{
    public function __construct(
        private readonly ResolveConfigurationService $resolveConfiguration,
    ) {}

    public function execute(string $key, ?int $tenantId = null): Result
    {
        return $this->resolveConfiguration->execute($key, $tenantId);
    }
}
