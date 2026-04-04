<?php
namespace Modules\UoM\Domain\RepositoryInterfaces;
use Modules\UoM\Domain\Entities\ProductUomSetting;

interface ProductUomSettingRepositoryInterface
{
    public function findById(int $id): ?ProductUomSetting;
    public function findByProduct(int $productId): ?ProductUomSetting;
    public function create(array $data): ProductUomSetting;
    public function update(ProductUomSetting $setting, array $data): ProductUomSetting;
}
