<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Contracts\ConfigurationCacheInterface;
use Modules\Core\Results\Result;

final class ClearConfigurationCacheService
{
    public function __construct(private readonly ConfigurationCacheInterface $cache) {}

    public function execute(): Result
    {
        $this->cache->flush();

        return Result::success(true);
    }
}
