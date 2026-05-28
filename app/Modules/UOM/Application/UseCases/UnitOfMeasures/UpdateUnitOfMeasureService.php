<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\UpdateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class UpdateUnitOfMeasureService implements UpdateUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $current = $this->repository->findById($id);

            if ($current === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UnitOfMeasure not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $current->get('tenant_id'));
            $name = (string) ($payload['name'] ?? $current->get('name'));

            foreach ($this->repository->list(['tenant_id' => $tenantId, 'name' => $name]) as $record) {
                if ((int) $record->get('id') !== (int) $id) {
                    return Result::failure(new Error(
                        UomErrorCode::DUPLICATE_NAME,
                        'Unit of measure name already exists.',
                    ));
                }
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
