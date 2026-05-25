<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnLines;

use Modules\Core\Application\Results\Result;

interface CreateGrnLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}