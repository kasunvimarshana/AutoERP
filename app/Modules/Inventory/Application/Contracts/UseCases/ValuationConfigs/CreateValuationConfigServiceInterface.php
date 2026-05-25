<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs;

use Modules\Core\Application\Results\Result;

interface CreateValuationConfigServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}