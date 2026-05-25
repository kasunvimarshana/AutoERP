<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\GdnLines;

use Modules\Core\Application\Results\Result;

interface UpdateGdnLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}