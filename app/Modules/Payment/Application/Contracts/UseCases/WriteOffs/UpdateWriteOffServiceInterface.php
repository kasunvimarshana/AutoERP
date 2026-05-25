<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\WriteOffs;

use Modules\Core\Application\Results\Result;

interface UpdateWriteOffServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}