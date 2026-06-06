<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\DTOs\ConfigurationMutationData;
use Modules\Configuration\Exceptions\ConfigurationNotFoundException;
use Modules\Configuration\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class UpdateConfigurationService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly SetConfigurationService $setConfiguration,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

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
