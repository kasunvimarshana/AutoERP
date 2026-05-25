<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\WriteOffs;

use Modules\Core\Application\Results\Result;

interface CreateWriteOffServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}