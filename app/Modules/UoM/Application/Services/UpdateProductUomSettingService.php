<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\UpdateProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\Events\ProductUomSettingUpdated;
use Modules\UoM\Domain\Exceptions\ProductUomSettingNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;

class UpdateProductUomSettingService extends BaseService implements UpdateProductUomSettingServiceInterface
{
    private ProductUomSettingRepositoryInterface $settingRepository;

    public function __construct(ProductUomSettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->settingRepository = $repository;
    }

    protected function handle(array $data): ProductUomSetting
    {
        $dto     = UpdateProductUomSettingData::fromArray($data);
        $id      = (int) ($dto->id ?? 0);
        $setting = $this->settingRepository->find($id);

        if (! $setting) {
            throw new ProductUomSettingNotFoundException($id);
        }

        $baseUomId = $dto->isProvided('baseUomId')
            ? $dto->baseUomId
            : $setting->getBaseUomId();

        $purchaseUomId = $dto->isProvided('purchaseUomId')
            ? $dto->purchaseUomId
            : $setting->getPurchaseUomId();

        $salesUomId = $dto->isProvided('salesUomId')
            ? $dto->salesUomId
            : $setting->getSalesUomId();

        $inventoryUomId = $dto->isProvided('inventoryUomId')
            ? $dto->inventoryUomId
            : $setting->getInventoryUomId();

        $purchaseFactor = $dto->isProvided('purchaseFactor')
            ? (float) $dto->purchaseFactor
            : $setting->getPurchaseFactor();

        $salesFactor = $dto->isProvided('salesFactor')
            ? (float) $dto->salesFactor
            : $setting->getSalesFactor();

        $inventoryFactor = $dto->isProvided('inventoryFactor')
            ? (float) $dto->inventoryFactor
            : $setting->getInventoryFactor();

        $isActive = $dto->isProvided('isActive')
            ? (bool) $dto->isActive
            : $setting->isActive();

        $setting->updateDetails(
            $baseUomId,
            $purchaseUomId,
            $salesUomId,
            $inventoryUomId,
            $purchaseFactor,
            $salesFactor,
            $inventoryFactor,
            $isActive
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new ProductUomSettingUpdated($saved));

        return $saved;
    }
}
