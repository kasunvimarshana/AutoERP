<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationFromCliServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Application\Results\Result;

final class SetConfigurationFromCliService implements SetConfigurationFromCliServiceInterface
{
    public function __construct(
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly SetConfigurationServiceInterface $setConfiguration,
    ) {
    }

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
