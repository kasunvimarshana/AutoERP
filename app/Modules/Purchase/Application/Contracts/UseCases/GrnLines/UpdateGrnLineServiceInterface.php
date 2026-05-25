<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnLines;

use Modules\Core\Application\Results\Result;

interface UpdateGrnLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}