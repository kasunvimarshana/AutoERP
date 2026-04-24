<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Application\DTOs\UomConversionData;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\Exceptions\UomConversionNotFoundException;
use Modules\Product\Domain\Exceptions\UomConversionRedundancyException;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class UpdateUomConversionService extends BaseService implements UpdateUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $uomConversionRepository)
    {
        parent::__construct($uomConversionRepository);
    }

    protected function handle(array $data): UomConversion
    {
        $id = (int) ($data['id'] ?? 0);
        $uomConversion = $this->uomConversionRepository->find($id);

        if (! $uomConversion) {
            throw new UomConversionNotFoundException($id);
        }

        $dto = UomConversionData::fromArray($data);

        $existingForward = $this->uomConversionRepository->findByUomPair(
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
        );

        if ($existingForward && $existingForward->getId() !== $id) {
            throw new UomConversionRedundancyException($dto->from_uom_id, $dto->to_uom_id);
        }

        $existingReverse = $this->uomConversionRepository->findByUomPair(
            fromUomId: $dto->to_uom_id,
            toUomId: $dto->from_uom_id,
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
        );

        if ($existingReverse && $existingReverse->getId() !== $id) {
            throw new UomConversionRedundancyException($dto->from_uom_id, $dto->to_uom_id);
        }

        $uomConversion->update(
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            factor: $dto->factor,
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            isBidirectional: $dto->is_bidirectional,
            isActive: $dto->is_active,
        );

        return $this->uomConversionRepository->save($uomConversion);
    }
}
