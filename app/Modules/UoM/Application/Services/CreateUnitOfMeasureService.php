<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Events\UnitOfMeasureCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class CreateUnitOfMeasureService extends BaseService implements CreateUnitOfMeasureServiceInterface
{
    private UnitOfMeasureRepositoryInterface $unitRepository;

    public function __construct(UnitOfMeasureRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->unitRepository = $repository;
    }

    protected function handle(array $data): UnitOfMeasure
    {
        $dto = UnitOfMeasureData::fromArray($data);

        $unit = new UnitOfMeasure(
            tenantId:      $dto->tenantId,
            uomCategoryId: $dto->uomCategoryId,
            name:          $dto->name,
            code:          $dto->code,
            symbol:        $dto->symbol,
            isBaseUnit:    $dto->isBaseUnit,
            factor:        $dto->factor,
            description:   $dto->description,
            isActive:      $dto->isActive,
        );

        $saved = $this->unitRepository->save($unit);
        $this->addEvent(new UnitOfMeasureCreated($saved));

        return $saved;
    }
}
