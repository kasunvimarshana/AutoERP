<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;

interface CreateUomConversionServiceInterface
{
    public function execute(UomConversionData $data): UomConversion;
}
