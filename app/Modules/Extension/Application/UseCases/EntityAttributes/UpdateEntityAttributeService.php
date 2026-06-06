<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\EntityAttributes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
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
