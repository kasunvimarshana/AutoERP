<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\UseCases\DeleteConfigurationServiceInterface;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Constants\ConfigurationScope;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Exceptions\ConfigurationNotFoundException;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteConfigurationService implements DeleteConfigurationServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationCacheInterface $cache,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationCacheKeyFactoryInterface $cacheKeyFactory,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {
    }

    public function execute(string $key, ?string $scope = null, ?int $tenantId = null): Result
    {
        try {
            $normalizedKey = $this->domain->normalizeKey($key);
            $normalizedScope = $this->domain->normalizeScope($scope);
            $resolvedTenantId = $this->resolveTenantId($normalizedScope, $tenantId);
            $deleted = $this->transactionManager->runInTransaction(
                fn () => $this->repository->deleteScopedByKey($normalizedKey, $resolvedTenantId),
            );

            if (! $deleted) {
                return Result::failure(new Error(
                    ConfigurationErrorCode::NOT_FOUND,
                    ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                    ['key' => $normalizedKey, 'tenant_id' => $resolvedTenantId],
                ));
            }

            $this->cache->forget($this->cacheKeyFactory->keyForConfiguration($normalizedKey, $resolvedTenantId));

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::TRANSACTION_FAILED,
                ['key' => $key, 'scope' => $scope, 'tenant_id' => $tenantId],
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
                ConfigurationErrorCode::INVALID_SCOPE . ': Organization unit scope is not supported by current schema.',
            );
        }

        $resolved = $tenantId ?? $this->tenantContext->currentTenantId();
        if ($resolved === null) {
            throw new \InvalidArgumentException(
                ConfigurationErrorCode::TENANT_CONTEXT_REQUIRED . ': Tenant scope requires tenant context.',
            );
        }

        return $resolved;
    }
}
