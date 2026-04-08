<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\SalesReturnServiceInterface;
use Modules\Returns\Domain\Entities\ReturnLine;
use Modules\Returns\Domain\Entities\SalesReturn;
use Modules\Returns\Domain\Events\SalesReturnApproved;
use Modules\Returns\Domain\Events\SalesReturnCompleted;
use Modules\Returns\Domain\Events\SalesReturnCreated;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;

class SalesReturnService implements SalesReturnServiceInterface
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $salesReturnRepository,
        private readonly ReturnLineRepositoryInterface $returnLineRepository,
    ) {}

    public function getSalesReturn(string $tenantId, string $id): SalesReturn
    {
        $entity = $this->salesReturnRepository->findById($tenantId, $id);

        if ($entity === null) {
            throw new NotFoundException("SalesReturn with id {$id} not found.");
        }

        return $entity;
    }

    public function getAllSalesReturns(string $tenantId): array
    {
        return $this->salesReturnRepository->findAll($tenantId);
    }

    public function createSalesReturn(string $tenantId, array $data): SalesReturn
    {
        return DB::transaction(function () use ($tenantId, $data): SalesReturn {
            $now = now();
            $returnId = (string) Str::uuid();

            $entity = new SalesReturn(
                id: $returnId,
                tenantId: $tenantId,
                salesOrderId: $data['sales_order_id'] ?? null,
                customerId: (string) $data['customer_id'],
                warehouseId: (string) $data['warehouse_id'],
                reference: (string) $data['reference'],
                status: $data['status'] ?? 'draft',
                returnDate: new \DateTimeImmutable($data['return_date']),
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
                totalAmount: (float) ($data['total_amount'] ?? 0),
                creditMemoNumber: isset($data['credit_memo_number']) ? (string) $data['credit_memo_number'] : null,
                refundAmount: (float) ($data['refund_amount'] ?? 0),
                restockingFee: (float) ($data['restocking_fee'] ?? 0),
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->salesReturnRepository->save($entity);

            foreach ($data['lines'] ?? [] as $lineData) {
                $line = new ReturnLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    returnType: 'sales',
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

            Event::dispatch(new SalesReturnCreated($entity));

            return $entity;
        });
    }

    public function approveSalesReturn(string $tenantId, string $id): SalesReturn
    {
        return DB::transaction(function () use ($tenantId, $id): SalesReturn {
            $entity = $this->getSalesReturn($tenantId, $id);
            $approved = $entity->approve();
            $this->salesReturnRepository->save($approved);
            Event::dispatch(new SalesReturnApproved($approved));

            return $approved;
        });
    }

    public function completeSalesReturn(string $tenantId, string $id): SalesReturn
    {
        return DB::transaction(function () use ($tenantId, $id): SalesReturn {
            $entity = $this->getSalesReturn($tenantId, $id);
            $completed = $entity->complete();
            $this->salesReturnRepository->save($completed);
            Event::dispatch(new SalesReturnCompleted($completed));

            return $completed;
        });
    }

    public function cancelSalesReturn(string $tenantId, string $id): SalesReturn
    {
        return DB::transaction(function () use ($tenantId, $id): SalesReturn {
            $entity = $this->getSalesReturn($tenantId, $id);

            if ($entity->isCancelled()) {
                throw new \InvalidArgumentException("SalesReturn with id {$id} is already cancelled.");
            }

            $cancelled = $entity->cancel();
            $this->salesReturnRepository->save($cancelled);

            return $cancelled;
        });
    }
}
