<?php

declare(strict_types=1);

namespace Modules\Item\Application\Contracts\UseCases\ComboItems;

use Modules\Core\Application\Results\Result;

interface CreateComboItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
