<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;
use Modules\Configuration\Application\Contracts\UseCases\DeleteConfigurationServiceInterface;
use Modules\Configuration\Application\Repositories\ConfigurationRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Exceptions\ConfigurationNotFoundException;
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
    ) {
    }

    public function execute(string $key): Result
    {
        try {
            $normalizedKey = $this->domain->normalizeKey($key);
            $deleted = $this->repository->deleteByKey($normalizedKey);

            if (! $deleted) {
                return Result::failure(new Error(
                    ConfigurationErrorCode::NOT_FOUND,
                    ConfigurationNotFoundException::forKey($normalizedKey)->getMessage(),
                    ['key' => $normalizedKey],
                ));
            }

            $this->cache->forget($this->cacheKeyFactory->keyForConfiguration($normalizedKey));

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_KEY, $exception->getMessage()));
        }
    }
}
