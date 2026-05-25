<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\Contracts\UseCases\SetConfigurationServiceInterface;
use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class SetConfigurationService implements SetConfigurationServiceInterface
{
    public function __construct(
        private readonly ConfigurationRepositoryInterface $repository,
        private readonly ConfigurationCacheInterface $cache,
        private readonly ConfigurationDomainServiceInterface $domain,
        private readonly ConfigurationRecordMapperInterface $recordMapper,
        private readonly ConfigurationCacheKeyFactoryInterface $cacheKeyFactory,
    ) {
    }

    public function execute(ConfigurationMutationData $data): Result
    {
        try {
            $key = $this->domain->normalizeKey($data->key);
            $source = $this->domain->normalizeSource($data->source);
            $description = $this->domain->normalizeDescription($data->description);

            [$storedValue, $valueType] = $this->domain->serializeValue($data->value);

            $attributes = [
                'key' => $key,
                'value' => $storedValue,
                'value_type' => $valueType,
                'source' => $source,
                'description' => $description,
            ];

            $current = $this->repository->findByKey($key);
            $record = $current instanceof DataRecord
                ? $this->repository->update($this->recordMapper->extractId($current), $attributes)
                : $this->repository->create($attributes);

            $resolved = $this->recordMapper->toValueData($record);
            $this->cache->put($this->cacheKeyFactory->keyForConfiguration($key), $resolved->toArray());

            return Result::success($resolved);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
