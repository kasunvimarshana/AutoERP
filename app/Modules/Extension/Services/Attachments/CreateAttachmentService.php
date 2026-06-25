<?php

declare(strict_types=1);

namespace Modules\Extension\Services\Attachments;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Services\ExtensionPayloadGuard;
use Throwable;

final class CreateAttachmentService
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repository,
        private readonly ExtensionPayloadGuard $payloadGuard,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $payload = $this->payloadGuard->forCreate($payload, 'attachable_type', 'attachable_id');

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Attachment data is invalid for the active tenant.',
            ));
        }
    }
}
