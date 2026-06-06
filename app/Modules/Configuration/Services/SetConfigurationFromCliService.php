<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\DTOs\ConfigurationMutationData;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Results\Result;

final class SetConfigurationFromCliService
{
    public function __construct(
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly SetConfigurationService $setConfiguration,
    ) {}

    public function execute(string $key, string $rawValue, ?string $source, ?string $description): Result
    {
        return $this->setConfiguration->execute(new ConfigurationMutationData(
            $key,
            $this->domain->parseCliValue($rawValue),
            $source,
            $description,
        ));
    }
}
