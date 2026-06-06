<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\Comments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class GetCommentService
{
    public function __construct(private readonly CommentRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Comment not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
