<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\Product\Application\DTOs\UnitOfMeasureData;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class CreateUnitOfMeasureService extends BaseService implements CreateUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository)
    {
        parent::__construct($unitOfMeasureRepository);
    }

    protected function handle(array $data): UnitOfMeasure
    {
        $dto = UnitOfMeasureData::fromArray($data);

        $unitOfMeasure = new UnitOfMeasure(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            symbol: $dto->symbol,
            type: $dto->type,
            isBase: $dto->is_base,
        );

        return $this->unitOfMeasureRepository->save($unitOfMeasure);
    }
}
