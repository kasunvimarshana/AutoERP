<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Results\Result;
use Throwable;

final class SetConfigurationService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationCacheInterface $cache,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
        private readonly ConfigurationCacheKeyFactoryInterface $cacheKeyFactory,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(ConfigurationMutationData $data): Result
    {
        try {
            $key = $this->domain->normalizeKey($data->key);
            $scope = $this->domain->normalizeScope($data->scope);
            $source = $this->domain->normalizeSource($data->source);
            $description = $this->domain->normalizeDescription($data->description);

            [$storedValue, $valueType] = $this->domain->serializeValue($data->value);
            $tenantId = $this->resolveTenantId($scope, $data->tenantId);

            $attributes = [
                'key' => $key,
                'value' => $storedValue,
                'value_type' => $valueType,
                'source' => $source,
                'description' => $description,
            ];

            if ($tenantId !== null) {
                $attributes['tenant_id'] = $tenantId;
            }

            $record = $this->transactionManager->runInTransaction(
                fn () => $this->repository->upsertScoped($key, $attributes, $tenantId),
            );

            $resolved = $this->recordMapper->toValueData($record);
            $this->cache->put(
                $this->cacheKeyFactory->keyForConfiguration($key, $tenantId),
                $resolved->toArray(),
            );

            return Result::success($resolved);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::TRANSACTION_FAILED,
                ['key' => $data->key, 'scope' => $data->scope, 'tenant_id' => $data->tenantId],
            ));
        }
    }

    private function resolveTenantId(string $scope, ?int $tenantId): ?int
    {
        if ($scope === ConfigurationScope::GLOBAL) {
            return null;
        }

        if ($scope === ConfigurationScope::ORGANIZATION_UNIT) {
            throw new \InvalidArgumentException(
                ConfigurationErrorCode::INVALID_SCOPE.': Organization unit scope is not supported by current schema.',
            );
        }

        $resolved = $tenantId ?? $this->tenantContext->currentTenantId();
        if ($resolved === null) {
            throw new \InvalidArgumentException(
                ConfigurationErrorCode::TENANT_CONTEXT_REQUIRED.': Tenant scope requires tenant context.',
            );
        }

        return $resolved;
    }
}
