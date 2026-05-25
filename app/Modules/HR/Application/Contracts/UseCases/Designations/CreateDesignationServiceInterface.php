<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Designations;

use Modules\Core\Application\Results\Result;

interface CreateDesignationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}