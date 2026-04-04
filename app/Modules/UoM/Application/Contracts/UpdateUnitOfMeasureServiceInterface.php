<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;

interface UpdateUnitOfMeasureServiceInterface
{
    public function execute(int $id, UnitOfMeasureData $data): UnitOfMeasure;
}
