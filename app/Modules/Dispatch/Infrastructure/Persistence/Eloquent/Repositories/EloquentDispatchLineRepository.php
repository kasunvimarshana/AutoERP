<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Dispatch\Domain\Entities\DispatchLine;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;

class EloquentDispatchLineRepository extends EloquentRepository implements DispatchLineRepositoryInterface
{
    public function __construct(DispatchLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (DispatchLineModel $m): DispatchLine => $this->mapModelToDomainEntity($m));
    }

    public function save(DispatchLine $line): DispatchLine
    {
        $savedModel = null;

        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'            => $line->getTenantId(),
                'dispatch_id'          => $line->getDispatchId(),
                'sales_order_line_id'  => $line->getSalesOrderLineId(),
                'product_id'           => $line->getProductId(),
                'product_variant_id'   => $line->getProductVariantId(),
                'description'          => $line->getDescription(),
                'quantity'             => $line->getQuantity(),
                'unit_of_measure'      => $line->getUnitOfMeasure(),
                'warehouse_location_id'=> $line->getWarehouseLocationId(),
                'batch_number'         => $line->getBatchNumber(),
                'serial_number'        => $line->getSerialNumber(),
                'status'               => $line->getStatus(),
                'weight'               => $line->getWeight(),
                'notes'                => $line->getNotes(),
                'metadata'             => $line->getMetadata()->toArray(),
            ];

            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof DispatchLineModel) {
            throw new \RuntimeException('Failed to save DispatchLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?DispatchLine
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByDispatch(int $dispatchId): Collection
    {
        return $this->model
            ->where('dispatch_id', $dispatchId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed
    {
        $query = $this->model->newQuery();

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get()->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(DispatchLineModel $model): DispatchLine
    {
        return new DispatchLine(
            tenantId:            $model->tenant_id,
            dispatchId:          $model->dispatch_id,
            productId:           $model->product_id,
            quantity:            (float) $model->quantity,
            salesOrderLineId:    $model->sales_order_line_id,
            productVariantId:    $model->product_variant_id,
            description:         $model->description,
            unitOfMeasure:       $model->unit_of_measure,
            warehouseLocationId: $model->warehouse_location_id,
            batchNumber:         $model->batch_number,
            serialNumber:        $model->serial_number,
            status:              $model->status,
            weight:              $model->weight !== null ? (float) $model->weight : null,
            notes:               $model->notes,
            metadata:            isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:                  $model->id,
            createdAt:           $model->created_at,
            updatedAt:           $model->updated_at,
        );
    }
}
