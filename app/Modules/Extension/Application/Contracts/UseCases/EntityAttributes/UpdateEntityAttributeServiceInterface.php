<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Result;

interface UpdateEntityAttributeServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}