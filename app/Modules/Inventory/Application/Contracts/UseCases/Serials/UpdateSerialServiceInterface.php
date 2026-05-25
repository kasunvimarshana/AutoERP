<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Serials;

use Modules\Core\Application\Results\Result;

interface UpdateSerialServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}