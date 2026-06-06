<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\Contracts\ConfigurationCacheInterface;
use Modules\Core\Application\Results\Result;

final class ClearConfigurationCacheService
{
    public function __construct(private readonly ConfigurationCacheInterface $cache) {}

    public function execute(): Result
    {
        $this->cache->flush();

        return Result::success(true);
    }
}
