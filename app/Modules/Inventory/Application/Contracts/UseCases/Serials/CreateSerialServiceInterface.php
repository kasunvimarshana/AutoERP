<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Serials;

use Modules\Core\Application\Results\Result;

interface CreateSerialServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}