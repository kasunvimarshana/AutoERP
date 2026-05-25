<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Comments;

use Modules\Core\Application\Results\Result;

interface ListCommentsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}