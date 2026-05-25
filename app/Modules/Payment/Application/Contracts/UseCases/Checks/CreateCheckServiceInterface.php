<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\Checks;

use Modules\Core\Application\Results\Result;

interface CreateCheckServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}