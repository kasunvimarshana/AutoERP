<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UomConversions;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\CreateUomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class CreateUomConversionService implements CreateUomConversionServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {
    }

    public function execute(array $payload): Result
    {
        try {
            $fromUomId = (int) ($payload['from_uom_id'] ?? 0);
            $toUomId = (int) ($payload['to_uom_id'] ?? 0);
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : null;
            $factor = (float) ($payload['factor'] ?? 0);

            if ($fromUomId === $toUomId) {
                return Result::failure(new Error(
                    UomErrorCode::SELF_REFERENCE_CONVERSION,
                    'From and to UOM cannot be the same.',
                ));
            }

            if ($factor <= 0) {
                return Result::failure(new Error(
                    UomErrorCode::INVALID_FACTOR,
                    'Conversion factor must be greater than zero.',
                ));
            }

            $fromUom = $this->uomRepository->findById($fromUomId);
            $toUom = $this->uomRepository->findById($toUomId);

            if ($fromUom === null || $toUom === null) {
                return Result::failure(new Error(
                    UomErrorCode::NOT_FOUND,
                    'One or both units of measure were not found.',
                ));
            }

            if ((string) $fromUom->get('type') !== (string) $toUom->get('type')) {
                return Result::failure(new Error(
                    UomErrorCode::INCOMPATIBLE_UOM_TYPE,
                    'Conversion types are incompatible.',
                ));
            }

            if ($this->repository->findConversionBetween($fromUomId, $toUomId, $tenantId, $itemId) !== null) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_CONVERSION, 'Conversion already exists.'));
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
