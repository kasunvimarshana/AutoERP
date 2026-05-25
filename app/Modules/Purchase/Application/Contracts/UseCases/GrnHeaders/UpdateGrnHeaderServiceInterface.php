<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnHeaders;

use Modules\Core\Application\Results\Result;

interface UpdateGrnHeaderServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}