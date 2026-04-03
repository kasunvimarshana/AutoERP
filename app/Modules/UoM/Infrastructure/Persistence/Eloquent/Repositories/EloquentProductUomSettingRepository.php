<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\ProductUomSettingModel;

class EloquentProductUomSettingRepository extends EloquentRepository implements ProductUomSettingRepositoryInterface
{
    public function __construct(ProductUomSettingModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductUomSettingModel $model): ProductUomSetting => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductUomSetting $setting): ProductUomSetting
    {
        $savedModel = null;

        DB::transaction(function () use ($setting, &$savedModel) {
            if ($setting->getId()) {
                $data = [
                    'tenant_id'        => $setting->getTenantId(),
                    'product_id'       => $setting->getProductId(),
                    'base_uom_id'      => $setting->getBaseUomId(),
                    'purchase_uom_id'  => $setting->getPurchaseUomId(),
                    'sales_uom_id'     => $setting->getSalesUomId(),
                    'inventory_uom_id' => $setting->getInventoryUomId(),
                    'purchase_factor'  => $setting->getPurchaseFactor(),
                    'sales_factor'     => $setting->getSalesFactor(),
                    'inventory_factor' => $setting->getInventoryFactor(),
                    'is_active'        => $setting->isActive(),
                ];
                $savedModel = $this->update($setting->getId(), $data);
            } else {
                $savedModel = $this->model->create([
                    'tenant_id'        => $setting->getTenantId(),
                    'product_id'       => $setting->getProductId(),
                    'base_uom_id'      => $setting->getBaseUomId(),
                    'purchase_uom_id'  => $setting->getPurchaseUomId(),
                    'sales_uom_id'     => $setting->getSalesUomId(),
                    'inventory_uom_id' => $setting->getInventoryUomId(),
                    'purchase_factor'  => $setting->getPurchaseFactor(),
                    'sales_factor'     => $setting->getSalesFactor(),
                    'inventory_factor' => $setting->getInventoryFactor(),
                    'is_active'        => $setting->isActive(),
                ]);
            }
        });

        if (! $savedModel instanceof ProductUomSettingModel) {
            throw new \RuntimeException('Failed to save ProductUomSetting.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByProduct(int $tenantId, int $productId): ?ProductUomSetting
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(ProductUomSettingModel $model): ProductUomSetting
    {
        return new ProductUomSetting(
            tenantId:        $model->tenant_id,
            productId:       $model->product_id,
            baseUomId:       $model->base_uom_id,
            purchaseUomId:   $model->purchase_uom_id,
            salesUomId:      $model->sales_uom_id,
            inventoryUomId:  $model->inventory_uom_id,
            purchaseFactor:  (float) $model->purchase_factor,
            salesFactor:     (float) $model->sales_factor,
            inventoryFactor: (float) $model->inventory_factor,
            isActive:        (bool) $model->is_active,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at
        );
    }
}
