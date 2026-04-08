<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\Events\ProductUomSettingCreated;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;

class CreateProductUomSettingService extends BaseService implements CreateProductUomSettingServiceInterface
{
    private ProductUomSettingRepositoryInterface $settingRepository;

    public function __construct(ProductUomSettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->settingRepository = $repository;
    }

    protected function handle(array $data): ProductUomSetting
    {
        $dto = ProductUomSettingData::fromArray($data);

        $setting = new ProductUomSetting(
            tenantId:        $dto->tenantId,
            productId:       $dto->productId,
            baseUomId:       $dto->baseUomId,
            purchaseUomId:   $dto->purchaseUomId,
            salesUomId:      $dto->salesUomId,
            inventoryUomId:  $dto->inventoryUomId,
            purchaseFactor:  $dto->purchaseFactor,
            salesFactor:     $dto->salesFactor,
            inventoryFactor: $dto->inventoryFactor,
            isActive:        $dto->isActive,
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new ProductUomSettingCreated($saved));

        return $saved;
    }
}
