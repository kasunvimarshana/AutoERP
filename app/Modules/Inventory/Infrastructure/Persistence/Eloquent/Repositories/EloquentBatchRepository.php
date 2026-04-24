<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;

class EloquentBatchRepository implements BatchRepositoryInterface
{
    public function __construct(private readonly BatchModel $model) {}

    public function save(Batch $batch): Batch
    {
        $data = [
            'tenant_id'       => $batch->getTenantId(),
            'product_id'      => $batch->getProductId(),
            'variant_id'      => $batch->getVariantId(),
            'batch_number'    => $batch->getBatchNumber(),
            'lot_number'      => $batch->getLotNumber(),
            'manufacture_date' => $batch->getManufactureDate(),
            'expiry_date'     => $batch->getExpiryDate(),
            'received_date'   => $batch->getReceivedDate(),
            'supplier_id'     => $batch->getSupplierId(),
            'status'          => $batch->getStatus(),
            'notes'           => $batch->getNotes(),
            'metadata'        => $batch->getMetadata(),
            'sales_price'     => $batch->getSalesPrice(),
        ];

        if ($batch->getId() !== null) {
            $this->model->newQuery()
                ->where('id', $batch->getId())
                ->update($data);

            return $batch;
        }

        /** @var BatchModel $created */
        $created = $this->model->newQuery()->create($data);
        $batch->setId($created->id);

        return $batch;
    }

    public function find(int $id): ?Batch
    {
        /** @var BatchModel|null $row */
        $row = $this->model->newQuery()->find($id);

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->where('id', $id)->delete();
    }

    public function findByTenant(
        int $tenantId,
        array $filters,
        int $perPage,
        int $page,
        string $sort,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if (isset($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (isset($filters['variant_id'])) {
            $query->where('variant_id', (int) $filters['variant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['batch_number'])) {
            $query->where('batch_number', 'like', '%' . $filters['batch_number'] . '%');
        }

        if (isset($filters['lot_number'])) {
            $query->where('lot_number', 'like', '%' . $filters['lot_number'] . '%');
        }

        [$column, $direction] = $this->parseSort($sort);
        $query->orderBy($column, $direction);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate(perPage: $perPage, columns: ['*'], pageName: 'page', page: $page);

        return $paginator->through(fn (BatchModel $row): Batch => $this->mapToEntity($row));
    }

    private function mapToEntity(BatchModel $row): Batch
    {
        return new Batch(
            tenantId:        (int) $row->tenant_id,
            productId:       (int) $row->product_id,
            variantId:       $row->variant_id !== null ? (int) $row->variant_id : null,
            batchNumber:     (string) $row->batch_number,
            lotNumber:       $row->lot_number !== null ? (string) $row->lot_number : null,
            manufactureDate: $row->manufacture_date !== null ? (string) $row->manufacture_date : null,
            expiryDate:      $row->expiry_date !== null ? (string) $row->expiry_date : null,
            receivedDate:    $row->received_date !== null ? (string) $row->received_date : null,
            supplierId:      $row->supplier_id !== null ? (int) $row->supplier_id : null,
            status:          (string) $row->status,
            notes:           $row->notes !== null ? (string) $row->notes : null,
            metadata:        is_array($row->metadata) ? $row->metadata : null,
            salesPrice:      $row->sales_price !== null ? (string) $row->sales_price : null,
            id:              (int) $row->id,
        );
    }

    private function parseSort(string $sort): array
    {
        if (str_starts_with($sort, '-')) {
            return [ltrim($sort, '-'), 'desc'];
        }

        return [$sort ?: 'id', 'asc'];
    }
}
