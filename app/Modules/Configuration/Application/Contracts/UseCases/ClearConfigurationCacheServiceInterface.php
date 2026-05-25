<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ClearConfigurationCacheServiceInterface
{
    public function execute(): Result;
}
