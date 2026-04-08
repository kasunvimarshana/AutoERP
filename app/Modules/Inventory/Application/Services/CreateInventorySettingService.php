<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\Events\InventorySettingCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class CreateInventorySettingService extends BaseService implements CreateInventorySettingServiceInterface
{
    private InventorySettingRepositoryInterface $settingRepository;

    public function __construct(InventorySettingRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->settingRepository = $repository;
    }

    protected function handle(array $data): InventorySetting
    {
        $dto = InventorySettingData::fromArray($data);

        $setting = new InventorySetting(
            tenantId:             $dto->tenantId,
            valuationMethod:      $dto->valuationMethod,
            managementMethod:     $dto->managementMethod,
            rotationStrategy:     $dto->rotationStrategy,
            allocationAlgorithm:  $dto->allocationAlgorithm,
            cycleCountMethod:     $dto->cycleCountMethod,
            negativeStockAllowed: $dto->negativeStockAllowed,
            trackLots:            $dto->trackLots,
            trackSerialNumbers:   $dto->trackSerialNumbers,
            trackExpiry:          $dto->trackExpiry,
            autoReorder:          $dto->autoReorder,
            lowStockAlert:        $dto->lowStockAlert,
            metadata:             $dto->metadata ? new Metadata($dto->metadata) : null,
            isActive:             $dto->isActive,
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new InventorySettingCreated($saved));

        return $saved;
    }
}
