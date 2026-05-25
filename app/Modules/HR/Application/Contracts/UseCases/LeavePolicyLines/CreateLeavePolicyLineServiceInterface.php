<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\LeavePolicyLines;

use Modules\Core\Application\Results\Result;

interface CreateLeavePolicyLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}