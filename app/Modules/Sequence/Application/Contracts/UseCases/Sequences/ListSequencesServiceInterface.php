<?php

declare(strict_types=1);

namespace Modules\Sequence\Application\Contracts\UseCases\Sequences;

use Modules\Core\Application\Results\Result;

interface ListSequencesServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result;
}
