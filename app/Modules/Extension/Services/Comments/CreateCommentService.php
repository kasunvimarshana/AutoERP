<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Comments;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\CommentRepositoryInterface;
use Throwable;

final class CreateCommentService
{
    public function __construct(private readonly CommentRepositoryInterface $repository) {}

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
