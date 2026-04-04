<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\Events\ProductUomSettingCreated;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class CreateProductUomSettingService implements CreateProductUomSettingServiceInterface
{
    public function __construct(
        private readonly ProductUomSettingRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {}

    public function execute(ProductUomSettingData $data): ProductUomSetting
    {
        $baseUom = $this->uomRepository->findById($data->baseUomId);
        if (!$baseUom) {
            throw new \DomainException("UnitOfMeasure not found: {$data->baseUomId}");
        }
        $setting = $this->repository->create($data->toArray());
        event(new ProductUomSettingCreated($baseUom->tenantId, $setting->id));
        return $setting;
    }
}
