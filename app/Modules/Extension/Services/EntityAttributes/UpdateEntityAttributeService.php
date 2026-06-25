<?php

declare(strict_types=1);

namespace Modules\Extension\Services\EntityAttributes;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Services\ExtensionPayloadGuard;
use Throwable;

final class UpdateEntityAttributeService
{
    public function __construct(
        private readonly EntityAttributeRepositoryInterface $repository,
        private readonly ExtensionPayloadGuard $payloadGuard,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->repository->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'Entity attribute not found.'));
            }

            $payload = $this->payloadGuard->forUpdate(
                $existing,
                $payload,
                'entity_type',
                'entity_id',
            );

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Entity attribute data is invalid for the active tenant.',
            ));
        }
    }
}
