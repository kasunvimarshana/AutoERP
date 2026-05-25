<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\UpdateConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Exceptions\ConfigurationNotFoundException;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;

final class UpdateConfigurationService implements UpdateConfigurationServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly SetConfigurationServiceInterface $setConfiguration,
    ) {
    }

    public function execute(string $key, ConfigurationMutationData $data): Result
    {
        $normalizedKey = $this->domain->normalizeKey($key);
        $lookup = $this->repository->findByKey($normalizedKey);

        if (! $lookup instanceof DataRecord) {
            return Result::failure(new Error(
                ConfigurationErrorCode::NOT_FOUND,
                ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                ['key' => $normalizedKey],
            ));
        }

        return $this->setConfiguration->execute(new ConfigurationMutationData(
            $normalizedKey,
            $data->value,
            $data->source,
            $data->description,
        ));
    }
}
