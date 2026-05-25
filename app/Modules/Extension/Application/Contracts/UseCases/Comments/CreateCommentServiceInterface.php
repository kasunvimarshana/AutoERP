<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Comments;

use Modules\Core\Application\Results\Result;

interface CreateCommentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}