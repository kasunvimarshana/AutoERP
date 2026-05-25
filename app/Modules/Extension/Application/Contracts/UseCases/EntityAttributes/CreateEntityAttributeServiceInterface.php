<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Result;

interface CreateEntityAttributeServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}