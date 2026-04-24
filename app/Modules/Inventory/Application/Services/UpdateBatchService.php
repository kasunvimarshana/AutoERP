<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Inventory\Domain\Entities\Batch;
use Modules\Inventory\Domain\Exceptions\BatchNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class UpdateBatchService implements UpdateBatchServiceInterface
{
    public function __construct(
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    ) {}

    public function execute(array $data): Batch
    {
        $id = (int) $data['id'];

        $batch = $this->batchRepository->find($id);

        if ($batch === null) {
            throw new BatchNotFoundException($id);
        }

        $batch->update(
            variantId:       isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchNumber:     (string) $data['batch_number'],
            lotNumber:       isset($data['lot_number']) ? (string) $data['lot_number'] : null,
            manufactureDate: isset($data['manufacture_date']) ? (string) $data['manufacture_date'] : null,
            expiryDate:      isset($data['expiry_date']) ? (string) $data['expiry_date'] : null,
            receivedDate:    isset($data['received_date']) ? (string) $data['received_date'] : null,
            supplierId:      isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            status:          (string) ($data['status'] ?? $batch->getStatus()),
            notes:           isset($data['notes']) ? (string) $data['notes'] : null,
            metadata:        is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
            salesPrice:      isset($data['sales_price']) ? (string) $data['sales_price'] : null,
        );

        $saved = DB::transaction(fn (): Batch => $this->batchRepository->save($batch));

        $this->refreshProjectionService->execute($batch->getTenantId(), $batch->getProductId());

        return $saved;
    }
}
