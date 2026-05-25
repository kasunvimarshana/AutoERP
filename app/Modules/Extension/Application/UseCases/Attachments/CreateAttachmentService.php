<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\Attachments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Contracts\UseCases\Attachments\CreateAttachmentServiceInterface;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class CreateAttachmentService implements CreateAttachmentServiceInterface
{
    public function __construct(private readonly AttachmentRepositoryInterface $repository)
    {
    }

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