<?php

declare(strict_types=1);

namespace Modules\Extension\Services\EntityAttributes;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;
use Throwable;

final class UpdateEntityAttributeService
{
    public function __construct(private readonly EntityAttributeRepositoryInterface $repository) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'EntityAttribute not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
