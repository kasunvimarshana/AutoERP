<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventorySettingData;
use Modules\Inventory\Domain\Events\InventorySettingUpdated;
use Modules\Inventory\Domain\Exceptions\InventorySettingNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;

class UpdateInventorySettingService extends BaseService implements UpdateInventorySettingServiceInterface
{
    public function __construct(private readonly InventorySettingRepositoryInterface $settingRepository)
    {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto     = UpdateInventorySettingData::fromArray($data);
        $setting = $this->settingRepository->find($dto->id);

        if (! $setting) {
            throw new InventorySettingNotFoundException($dto->id);
        }

        $setting->updateDetails(
            valuationMethod:      $dto->valuationMethod      ?? $setting->getValuationMethod(),
            managementMethod:     $dto->managementMethod     ?? $setting->getManagementMethod(),
            rotationStrategy:     $dto->rotationStrategy     ?? $setting->getRotationStrategy(),
            allocationAlgorithm:  $dto->allocationAlgorithm  ?? $setting->getAllocationAlgorithm(),
            cycleCountMethod:     $dto->cycleCountMethod      ?? $setting->getCycleCountMethod(),
            negativeStockAllowed: $dto->negativeStockAllowed  ?? $setting->isNegativeStockAllowed(),
            trackLots:            $dto->trackLots             ?? $setting->isTrackLots(),
            trackSerialNumbers:   $dto->trackSerialNumbers    ?? $setting->isTrackSerialNumbers(),
            trackExpiry:          $dto->trackExpiry           ?? $setting->isTrackExpiry(),
            autoReorder:          $dto->autoReorder           ?? $setting->isAutoReorder(),
            lowStockAlert:        $dto->lowStockAlert         ?? $setting->isLowStockAlert(),
            metadata:             $dto->metadata !== null ? new Metadata($dto->metadata) : $setting->getMetadata(),
            isActive:             $dto->isActive              ?? $setting->isActive(),
        );

        $saved = $this->settingRepository->save($setting);
        $this->addEvent(new InventorySettingUpdated($saved));

        return $saved;
    }
}
