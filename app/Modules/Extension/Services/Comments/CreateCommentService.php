<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Comments;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\CommentRepositoryInterface;
use Modules\Extension\Services\ExtensionPayloadGuard;
use Throwable;

final class CreateCommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $repository,
        private readonly ExtensionPayloadGuard $payloadGuard,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $payload = $this->payloadGuard->forCreate($payload, 'commentable_type', 'commentable_id');

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Comment data is invalid for the active tenant.',
            ));
        }
    }
}
