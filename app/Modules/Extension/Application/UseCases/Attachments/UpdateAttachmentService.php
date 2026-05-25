<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\Attachments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Contracts\UseCases\Attachments\UpdateAttachmentServiceInterface;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class UpdateAttachmentService implements UpdateAttachmentServiceInterface
{
    public function __construct(private readonly AttachmentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Attachment not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}