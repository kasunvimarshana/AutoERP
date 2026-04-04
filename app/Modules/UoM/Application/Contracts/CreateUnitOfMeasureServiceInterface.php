<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface CreateUnitOfMeasureServiceInterface
{
    public function execute(UnitOfMeasureData $data): UnitOfMeasure;
}
