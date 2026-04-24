<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\DTOs\UomConversionData;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\Exceptions\UomConversionRedundancyException;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class CreateUomConversionService extends BaseService implements CreateUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $uomConversionRepository)
    {
        parent::__construct($uomConversionRepository);
    }

    protected function handle(array $data): UomConversion
    {
        $dto = UomConversionData::fromArray($data);

        $existingForward = $this->uomConversionRepository->findByUomPair(
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
        );

        $existingReverse = $this->uomConversionRepository->findByUomPair(
            fromUomId: $dto->to_uom_id,
            toUomId: $dto->from_uom_id,
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
        );

        if ($existingForward || $existingReverse) {
            throw new UomConversionRedundancyException($dto->from_uom_id, $dto->to_uom_id);
        }

        $uomConversion = new UomConversion(
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            factor: $dto->factor,
            isBidirectional: $dto->is_bidirectional,
            isActive: $dto->is_active,
        );

        return $this->uomConversionRepository->save($uomConversion);
    }
}
