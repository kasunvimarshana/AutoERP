<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\PurchaseReturnServiceInterface;
use Modules\Returns\Domain\Entities\PurchaseReturn;
use Modules\Returns\Domain\Entities\ReturnLine;
use Modules\Returns\Domain\Events\PurchaseReturnApproved;
use Modules\Returns\Domain\Events\PurchaseReturnCompleted;
use Modules\Returns\Domain\Events\PurchaseReturnCreated;
use Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnLineRepositoryInterface;

class PurchaseReturnService implements PurchaseReturnServiceInterface
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $purchaseReturnRepository,
        private readonly ReturnLineRepositoryInterface $returnLineRepository,
    ) {}

    public function getPurchaseReturn(string $tenantId, string $id): PurchaseReturn
    {
        $entity = $this->purchaseReturnRepository->findById($tenantId, $id);

        if ($entity === null) {
            throw new NotFoundException("PurchaseReturn with id {$id} not found.");
        }

        return $entity;
    }

    public function getAllPurchaseReturns(string $tenantId): array
    {
        return $this->purchaseReturnRepository->findAll($tenantId);
    }

    public function createPurchaseReturn(string $tenantId, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($tenantId, $data): PurchaseReturn {
            $now = now();
            $returnId = (string) Str::uuid();

            $entity = new PurchaseReturn(
                id: $returnId,
                tenantId: $tenantId,
                purchaseOrderId: $data['purchase_order_id'] ?? null,
                supplierId: (string) $data['supplier_id'],
                warehouseId: (string) $data['warehouse_id'],
                reference: (string) $data['reference'],
                status: $data['status'] ?? 'draft',
                returnDate: new \DateTimeImmutable($data['return_date']),
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
                totalAmount: (float) ($data['total_amount'] ?? 0),
                creditMemoNumber: isset($data['credit_memo_number']) ? (string) $data['credit_memo_number'] : null,
                refundAmount: (float) ($data['refund_amount'] ?? 0),
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->purchaseReturnRepository->save($entity);

            foreach ($data['lines'] ?? [] as $lineData) {
                $line = new ReturnLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    returnType: 'purchase',
                    returnId: $returnId,
                    productId: (string) $lineData['product_id'],
                    variantId: isset($lineData['variant_id']) ? (string) $lineData['variant_id'] : null,
                    quantity: (float) $lineData['quantity'],
                    unitPrice: (float) $lineData['unit_price'],
                    lineTotal: (float) $lineData['line_total'],
                    batchNumber: isset($lineData['batch_number']) ? (string) $lineData['batch_number'] : null,
                    lotNumber: isset($lineData['lot_number']) ? (string) $lineData['lot_number'] : null,
                    serialNumber: isset($lineData['serial_number']) ? (string) $lineData['serial_number'] : null,
                    condition: (string) ($lineData['condition'] ?? 'good'),
                    restockable: (bool) ($lineData['restockable'] ?? true),
                    qualityNotes: isset($lineData['quality_notes']) ? (string) $lineData['quality_notes'] : null,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $this->returnLineRepository->save($line);
            }

            Event::dispatch(new PurchaseReturnCreated($entity));

            return $entity;
        });
    }

    public function approvePurchaseReturn(string $tenantId, string $id): PurchaseReturn
    {
        return DB::transaction(function () use ($tenantId, $id): PurchaseReturn {
            $entity = $this->getPurchaseReturn($tenantId, $id);
            $approved = $entity->approve();
            $this->purchaseReturnRepository->save($approved);
            Event::dispatch(new PurchaseReturnApproved($approved));

            return $approved;
        });
    }

    public function completePurchaseReturn(string $tenantId, string $id): PurchaseReturn
    {
        return DB::transaction(function () use ($tenantId, $id): PurchaseReturn {
            $entity = $this->getPurchaseReturn($tenantId, $id);
            $completed = $entity->complete();
            $this->purchaseReturnRepository->save($completed);
            Event::dispatch(new PurchaseReturnCompleted($completed));

            return $completed;
        });
    }

    public function cancelPurchaseReturn(string $tenantId, string $id): PurchaseReturn
    {
        return DB::transaction(function () use ($tenantId, $id): PurchaseReturn {
            $entity = $this->getPurchaseReturn($tenantId, $id);

            if ($entity->isCancelled()) {
                throw new \InvalidArgumentException("PurchaseReturn with id {$id} is already cancelled.");
            }

            $cancelled = $entity->cancel();
            $this->purchaseReturnRepository->save($cancelled);

            return $cancelled;
        });
    }
}
