<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationSource;
use Modules\Configuration\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\DTOs\ConfigurationValueData;
use Modules\Configuration\Exceptions\ConfigurationNotFoundException;
use Modules\Configuration\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class ResolveConfigurationService
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationCacheInterface $cache,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
        private readonly ConfigurationCacheKeyFactoryInterface $cacheKeyFactory,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(
        string $key,
        ?int $tenantId = null,
        ?int $organizationUnitId = null,
        mixed $defaultValue = null,
    ): Result {
        try {
            $normalizedKey = $this->domain->normalizeKey($key);
            $resolvedTenantId = $tenantId ?? $this->tenantContext->currentTenantId();
            $cacheKey = $this->cacheKeyFactory->keyForConfiguration($normalizedKey, $resolvedTenantId);

            if ($this->cache->has($cacheKey)) {
                $cached = $this->cache->get($cacheKey);

                if (is_array($cached)) {
                    return Result::success(ConfigurationValueData::fromArray($cached));
                }
            }

            $record = $this->repository->findResolvedByScope($normalizedKey, $resolvedTenantId);
            if ($record instanceof DataRecord) {
                $data = $this->recordMapper->toValueData($record);
                $resolvedFrom = $data->scope ?? ConfigurationScope::GLOBAL;

                $resolved = new ConfigurationValueData(
                    $data->key,
                    $data->value,
                    $data->source,
                    $data->description,
                    $data->updatedAt,
                    $data->scope,
                    $data->tenantId,
                    $organizationUnitId,
                    $resolvedFrom,
                );

                $this->cache->put($cacheKey, $resolved->toArray());

                return Result::success($resolved);
            }

            if (func_num_args() >= 4) {
                $resolved = new ConfigurationValueData(
                    $normalizedKey,
                    $defaultValue,
                    ConfigurationSource::RUNTIME,
                    null,
                    null,
                    ConfigurationScope::GLOBAL,
                    $resolvedTenantId,
                    $organizationUnitId,
                    'default',
                );

                return Result::success($resolved);
            }

            return Result::failure(new Error(
                ConfigurationErrorCode::NOT_FOUND,
                ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                ['key' => $normalizedKey, 'tenant_id' => $resolvedTenantId],
            ));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::INVALID_KEY,
                ['key' => $key, 'tenant_id' => $tenantId],
            ));
        }
    }
}
