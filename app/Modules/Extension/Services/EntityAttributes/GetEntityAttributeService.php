<?php

declare(strict_types=1);

namespace Modules\Extension\Services\EntityAttributes;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;
use Throwable;

final class GetEntityAttributeService
{
    public function __construct(private readonly EntityAttributeRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(ExtensionErrorCode::NOT_FOUND, 'EntityAttribute not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Unable to retrieve the entity attribute for the active tenant.',
            ));
        }
    }
}
