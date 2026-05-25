<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases;

use Modules\Configuration\Application\DTOs\ConfigurationMutationData;
use Modules\Core\Application\Results\Result;

interface SetConfigurationServiceInterface
{
    public function execute(ConfigurationMutationData $data): Result;
}
