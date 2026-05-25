<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface GetConfigurationServiceInterface
{
    public function execute(string $key): Result;
}
