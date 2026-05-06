<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\Product\Application\DTOs\UnitOfMeasureData;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UpdateUnitOfMeasureService extends BaseService implements UpdateUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository)
    {
        parent::__construct($unitOfMeasureRepository);
    }

    protected function handle(array $data): UnitOfMeasure
    {
        $id = (int) ($data['id'] ?? 0);
        $unitOfMeasure = $this->unitOfMeasureRepository->find($id);

        if (! $unitOfMeasure) {
            throw new UnitOfMeasureNotFoundException($id);
        }

        $dto = UnitOfMeasureData::fromArray($data);


        if ($dto->rowVersion !== $unitOfMeasure->getRowVersion()) {
            throw new ConcurrentModificationException('UnitOfMeasure', $id);
        }

        $unitOfMeasure->update(
            name: $dto->name,
            symbol: $dto->symbol,
            type: $dto->type,
            isBase: $dto->isBase,
        );

        return $this->unitOfMeasureRepository->save($unitOfMeasure);
    }
}
