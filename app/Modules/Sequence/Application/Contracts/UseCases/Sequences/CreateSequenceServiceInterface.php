<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Contracts\UseCases\Sequences;

use Modules\Core\Application\Results\Result;

interface CreateSequenceServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
