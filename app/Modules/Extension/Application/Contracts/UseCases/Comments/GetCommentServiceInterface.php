<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Contracts\UseCases\Comments;

use Modules\Core\Application\Results\Result;

interface GetCommentServiceInterface
{
    public function execute(int|string $id): Result;
}