<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UpdateUnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Events\UnitOfMeasureUpdated;
use Modules\UoM\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UpdateUnitOfMeasureService extends BaseService implements UpdateUnitOfMeasureServiceInterface
{
    private UnitOfMeasureRepositoryInterface $unitRepository;

    public function __construct(UnitOfMeasureRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->unitRepository = $repository;
    }

    protected function handle(array $data): UnitOfMeasure
    {
        $dto  = UpdateUnitOfMeasureData::fromArray($data);
        $id   = (int) ($dto->id ?? 0);
        $unit = $this->unitRepository->find($id);

        if (! $unit) {
            throw new UnitOfMeasureNotFoundException($id);
        }

        $uomCategoryId = $dto->isProvided('uomCategoryId')
            ? (int) $dto->uomCategoryId
            : $unit->getUomCategoryId();

        $name = $dto->isProvided('name')
            ? (string) $dto->name
            : $unit->getName();

        $code = $dto->isProvided('code')
            ? (string) $dto->code
            : $unit->getCode();

        $symbol = $dto->isProvided('symbol')
            ? (string) $dto->symbol
            : $unit->getSymbol();

        $isBaseUnit = $dto->isProvided('isBaseUnit')
            ? (bool) $dto->isBaseUnit
            : $unit->isBaseUnit();

        $factor = $dto->isProvided('factor')
            ? (float) $dto->factor
            : $unit->getFactor();

        $description = $dto->isProvided('description')
            ? $dto->description
            : $unit->getDescription();

        $isActive = $dto->isProvided('isActive')
            ? (bool) $dto->isActive
            : $unit->isActive();

        $unit->updateDetails($uomCategoryId, $name, $code, $symbol, $isBaseUnit, $factor, $description, $isActive);

        $saved = $this->unitRepository->save($unit);
        $this->addEvent(new UnitOfMeasureUpdated($saved));

        return $saved;
    }
}
