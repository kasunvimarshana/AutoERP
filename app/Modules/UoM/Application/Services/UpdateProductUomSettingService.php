<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\Events\ProductUomSettingUpdated;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UpdateProductUomSettingService implements UpdateProductUomSettingServiceInterface
{
    public function __construct(
        private readonly ProductUomSettingRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {}

    public function execute(int $id, ProductUomSettingData $data): ProductUomSetting
    {
        $setting = $this->repository->findById($id);
        if (!$setting) {
            throw new \DomainException("ProductUomSetting not found: {$id}");
        }
        $baseUom = $this->uomRepository->findById($data->baseUomId);
        if (!$baseUom) {
            throw new \DomainException("UnitOfMeasure not found: {$data->baseUomId}");
        }
        $updated = $this->repository->update($setting, $data->toArray());
        event(new ProductUomSettingUpdated($baseUom->tenantId, $id));
        return $updated;
    }
}
