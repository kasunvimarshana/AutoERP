<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\Comments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class DeleteCommentService
{
    public function __construct(private readonly CommentRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Comment not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
