<?php

declare(strict_types=1);

namespace Modules\Extension\Services\EntityAttributes;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Services\ExtensionPayloadGuard;
use Throwable;

final class CreateEntityAttributeService
{
    public function __construct(
        private readonly EntityAttributeRepositoryInterface $repository,
        private readonly ExtensionPayloadGuard $payloadGuard,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $payload = $this->payloadGuard->forCreate($payload, 'entity_type', 'entity_id');

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Entity attribute data is invalid for the active tenant.',
            ));
        }
    }
}
