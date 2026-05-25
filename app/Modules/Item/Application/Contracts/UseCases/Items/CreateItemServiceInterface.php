<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\Items;

use Modules\Core\Application\Results\Result;

interface CreateItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
