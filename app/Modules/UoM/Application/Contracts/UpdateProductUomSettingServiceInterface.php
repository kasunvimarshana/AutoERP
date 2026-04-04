<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;

interface UpdateProductUomSettingServiceInterface
{
    public function execute(int $id, ProductUomSettingData $data): ProductUomSetting;
}
