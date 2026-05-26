<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\UpdateConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Exceptions\ConfigurationNotFoundException;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateConfigurationService implements UpdateConfigurationServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly SetConfigurationServiceInterface $setConfiguration,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(string $key, ConfigurationMutationData $data): Result
    {
        try {
            $normalizedKey = $this->domain->normalizeKey($key);
            $scope = $this->domain->normalizeScope($data->scope);
            $tenantId = $scope === ConfigurationScope::TENANT
                ? ($data->tenantId ?? $this->tenantContext->currentTenantId())
                : null;

            $lookup = $tenantId === null
                ? $this->repository->findByKey($normalizedKey)
                : $this->repository->findByTenantAndKey($tenantId, $normalizedKey);

            if (! $lookup instanceof DataRecord) {
                return Result::failure(new Error(
                    ConfigurationErrorCode::NOT_FOUND,
                    ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                    ['key' => $normalizedKey, 'tenant_id' => $tenantId],
                ));
            }

            return $this->setConfiguration->execute(new ConfigurationMutationData(
                $normalizedKey,
                $data->value,
                $data->source,
                $data->description,
                $scope,
                $tenantId,
                $data->organizationUnitId,
            ));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::INVALID_VALUE,
                ['key' => $key, 'scope' => $data->scope, 'tenant_id' => $data->tenantId],
            ));
        }
    }
}
