<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;

interface CreateProductUomSettingServiceInterface
{
    public function execute(ProductUomSettingData $data): ProductUomSetting;
}
