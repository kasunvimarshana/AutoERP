<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\Product\Application\DTOs\UomConversionData;
use Modules\Product\Domain\Entities\UomConversion;
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

        $uomConversion = new UomConversion(
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            factor: $dto->factor,
        );

        return $this->uomConversionRepository->save($uomConversion);
    }
}
