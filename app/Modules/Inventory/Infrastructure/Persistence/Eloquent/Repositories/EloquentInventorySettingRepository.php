<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySettingModel;

class EloquentInventorySettingRepository extends EloquentRepository implements InventorySettingRepositoryInterface
{
    public function __construct(InventorySettingModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventorySettingModel $m): InventorySetting => $this->mapModelToDomainEntity($m));
    }

    public function save(InventorySetting $setting): InventorySetting
    {
        $savedModel = null;
        DB::transaction(function () use ($setting, &$savedModel) {
            $data = [
                'tenant_id'             => $setting->getTenantId(),
                'valuation_method'      => $setting->getValuationMethod(),
                'management_method'     => $setting->getManagementMethod(),
                'rotation_strategy'     => $setting->getRotationStrategy(),
                'allocation_algorithm'  => $setting->getAllocationAlgorithm(),
                'cycle_count_method'    => $setting->getCycleCountMethod(),
                'negative_stock_allowed'=> $setting->isNegativeStockAllowed(),
                'track_lots'            => $setting->isTrackLots(),
                'track_serial_numbers'  => $setting->isTrackSerialNumbers(),
                'track_expiry'          => $setting->isTrackExpiry(),
                'auto_reorder'          => $setting->isAutoReorder(),
                'low_stock_alert'       => $setting->isLowStockAlert(),
                'metadata'              => $setting->getMetadata()->toArray(),
                'is_active'             => $setting->isActive(),
            ];
            if ($setting->getId()) {
                $savedModel = $this->update($setting->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventorySettingModel) {
            throw new \RuntimeException('Failed to save InventorySetting.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByTenant(int $tenantId): ?InventorySetting
    {
        $model = $this->model->where('tenant_id', $tenantId)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(InventorySettingModel $model): InventorySetting
    {
        return new InventorySetting(
            tenantId:             $model->tenant_id,
            valuationMethod:      $model->valuation_method,
            managementMethod:     $model->management_method,
            rotationStrategy:     $model->rotation_strategy,
            allocationAlgorithm:  $model->allocation_algorithm,
            cycleCountMethod:     $model->cycle_count_method,
            negativeStockAllowed: (bool) $model->negative_stock_allowed,
            trackLots:            (bool) $model->track_lots,
            trackSerialNumbers:   (bool) $model->track_serial_numbers,
            trackExpiry:          (bool) $model->track_expiry,
            autoReorder:          (bool) $model->auto_reorder,
            lowStockAlert:        (bool) $model->low_stock_alert,
            metadata:             isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:             (bool) $model->is_active,
            id:                   $model->id,
            createdAt:            $model->created_at,
            updatedAt:            $model->updated_at,
        );
    }
}
