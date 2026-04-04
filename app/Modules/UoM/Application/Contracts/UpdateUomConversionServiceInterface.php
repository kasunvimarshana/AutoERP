<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;

interface UpdateUomConversionServiceInterface
{
    public function execute(int $id, UomConversionData $data): UomConversion;
}
