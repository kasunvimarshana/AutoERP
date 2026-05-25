<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Contracts\UseCases\Sequences;

use Modules\Core\Application\Results\Result;

interface UpdateSequenceServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
