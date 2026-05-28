<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\CreateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class CreateUnitOfMeasureService implements CreateUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (
                isset($payload['tenant_id'], $payload['name'])
                && $this->repository->exists([
                    'tenant_id' => (int) $payload['tenant_id'],
                    'name' => (string) $payload['name'],
                ])
            ) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_NAME, 'Unit of measure name already exists.'));
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
