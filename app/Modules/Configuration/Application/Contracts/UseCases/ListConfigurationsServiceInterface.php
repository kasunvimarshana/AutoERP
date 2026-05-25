<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Configuration\Application\DTOs\ConfigurationQueryData;
use Modules\Core\Application\Results\Result;

interface ListConfigurationsServiceInterface
{
    public function execute(ConfigurationQueryData $query): Result;
}
