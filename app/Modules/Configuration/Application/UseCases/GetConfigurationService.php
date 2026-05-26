<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\UseCases\GetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\ResolveConfigurationServiceInterface;
use Modules\Core\Application\Results\Result;

final class GetConfigurationService implements GetConfigurationServiceInterface
{
    public function __construct(
        private readonly ResolveConfigurationServiceInterface $resolveConfiguration,
    ) {
    }

    public function execute(string $key, ?int $tenantId = null): Result
    {
        return $this->resolveConfiguration->execute($key, $tenantId);
    }
}
