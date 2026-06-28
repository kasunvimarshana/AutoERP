<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Attachments;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\AttachmentRepositoryInterface;
use Throwable;

final class GetAttachmentService
{
    public function __construct(private readonly AttachmentRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Attachment not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Unable to retrieve the attachment for the active tenant.',
            ));
        }
    }
}
