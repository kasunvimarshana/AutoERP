<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryValuationLayerModel;

class EloquentInventoryValuationLayerRepository extends EloquentRepository implements InventoryValuationLayerRepositoryInterface
{
    public function __construct(InventoryValuationLayerModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (InventoryValuationLayerModel $m): InventoryValuationLayer => $this->mapModelToDomainEntity($m));
    }

    public function save(InventoryValuationLayer $layer): InventoryValuationLayer
    {
        $savedModel = null;
        DB::transaction(function () use ($layer, &$savedModel) {
            $data = [
                'tenant_id'        => $layer->getTenantId(),
                'product_id'       => $layer->getProductId(),
                'variation_id'     => $layer->getVariationId(),
                'batch_id'         => $layer->getBatchId(),
                'location_id'      => $layer->getLocationId(),
                'layer_date'       => $layer->getLayerDate()->format('Y-m-d'),
                'qty_in'           => $layer->getQtyIn(),
                'qty_remaining'    => $layer->getQtyRemaining(),
                'unit_cost'        => $layer->getUnitCost(),
                'currency'         => $layer->getCurrency(),
                'valuation_method' => $layer->getValuationMethod(),
                'reference_type'   => $layer->getReferenceType(),
                'reference_id'     => $layer->getReferenceId(),
                'is_closed'        => $layer->isClosed(),
                'metadata'         => $layer->getMetadata()->toArray(),
            ];
            if ($layer->getId()) {
                $savedModel = $this->update($layer->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof InventoryValuationLayerModel) {
            throw new \RuntimeException('Failed to save InventoryValuationLayer.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findOpenLayers(int $tenantId, int $productId, string $valuationMethod): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)
            ->where('valuation_method', $valuationMethod)->where('is_closed', false)
            ->orderBy('layer_date')->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->where('product_id', $productId)->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(InventoryValuationLayerModel $model): InventoryValuationLayer
    {
        return new InventoryValuationLayer(
            tenantId:        $model->tenant_id,
            productId:       $model->product_id,
            layerDate:       $model->layer_date instanceof \DateTimeInterface ? $model->layer_date : new \DateTimeImmutable((string) $model->layer_date),
            qtyIn:           (float) $model->qty_in,
            unitCost:        (float) $model->unit_cost,
            valuationMethod: $model->valuation_method,
            variationId:     $model->variation_id,
            batchId:         $model->batch_id,
            locationId:      $model->location_id,
            qtyRemaining:    (float) $model->qty_remaining,
            currency:        $model->currency,
            referenceType:   $model->reference_type,
            referenceId:     $model->reference_id,
            isClosed:        (bool) $model->is_closed,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
