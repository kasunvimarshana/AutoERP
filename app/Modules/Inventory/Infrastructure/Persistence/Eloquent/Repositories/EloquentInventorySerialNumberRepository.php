<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySerialNumberModel;

class EloquentInventorySerialNumberRepository extends EloquentRepository implements InventorySerialNumberRepositoryInterface
{
    public function __construct(InventorySerialNumberModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventorySerialNumberModel $m): InventorySerialNumber => $this->mapModelToDomainEntity($m));
    }

    public function save(InventorySerialNumber $serial): InventorySerialNumber
    {
        $savedModel = null;
        DB::transaction(function () use ($serial, &$savedModel) {
            $data = [
                'tenant_id'      => $serial->getTenantId(),
                'product_id'     => $serial->getProductId(),
                'variation_id'   => $serial->getVariationId(),
                'batch_id'       => $serial->getBatchId(),
                'serial_number'  => $serial->getSerialNumber(),
                'location_id'    => $serial->getLocationId(),
                'status'         => $serial->getStatus(),
                'purchase_price' => $serial->getPurchasePrice(),
                'currency'       => $serial->getCurrency(),
                'purchased_at'   => $serial->getPurchasedAt()?->format('Y-m-d H:i:s'),
                'sold_at'        => $serial->getSoldAt()?->format('Y-m-d H:i:s'),
                'returned_at'    => $serial->getReturnedAt()?->format('Y-m-d H:i:s'),
                'notes'          => $serial->getNotes(),
                'metadata'       => $serial->getMetadata()->toArray(),
            ];
            if ($serial->getId()) {
                $savedModel = $this->update($serial->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventorySerialNumberModel) {
            throw new \RuntimeException('Failed to save InventorySerialNumber.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findBySerial(int $tenantId, int $productId, string $serial): ?InventorySerialNumber
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)
            ->where('serial_number', $serial)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByLocation(int $tenantId, int $locationId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('location_id', $locationId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(InventorySerialNumberModel $model): InventorySerialNumber
    {
        return new InventorySerialNumber(
            tenantId:      $model->tenant_id,
            productId:     $model->product_id,
            serialNumber:  $model->serial_number,
            variationId:   $model->variation_id,
            batchId:       $model->batch_id,
            locationId:    $model->location_id,
            status:        $model->status,
            purchasePrice: isset($model->purchase_price) ? (float) $model->purchase_price : null,
            currency:      $model->currency,
            purchasedAt:   $model->purchased_at,
            soldAt:        $model->sold_at,
            returnedAt:    $model->returned_at,
            notes:         $model->notes,
            metadata:      isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at,
        );
    }
}
