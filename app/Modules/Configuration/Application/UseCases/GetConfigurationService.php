<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\Contracts\UseCases\GetConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Exceptions\ConfigurationNotFoundException;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetConfigurationService implements GetConfigurationServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationCacheInterface $cache,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
        private readonly ConfigurationCacheKeyFactoryInterface $cacheKeyFactory,
    ) {
    }

    public function execute(string $key): Result
    {
        try {
            $normalizedKey = $this->domain->normalizeKey($key);
            $cacheKey = $this->cacheKeyFactory->keyForConfiguration($normalizedKey);

            if ($this->cache->has($cacheKey)) {
                $cached = $this->cache->get($cacheKey);

                if (is_array($cached)) {
                    return Result::success(ConfigurationValueData::fromArray($cached));
                }
            }

            $record = $this->repository->findByKey($normalizedKey);
            if ($record instanceof DataRecord) {
                $data = $this->recordMapper->toValueData($record);
                $this->cache->put($cacheKey, $data->toArray());

                return Result::success($data);
            }

            return Result::failure(new Error(
                ConfigurationErrorCode::NOT_FOUND,
                ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                ['key' => $normalizedKey],
            ));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
