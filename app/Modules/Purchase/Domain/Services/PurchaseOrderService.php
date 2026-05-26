<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sequence\Domain\Services\SequenceService;
use Modules\Purchase\Domain\Enums\PurchaseOrderStatus;
use Modules\Purchase\Domain\Events\PurchaseOrderConfirmed;
use Modules\Purchase\Domain\Events\PurchaseOrderCreated;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;

final class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseAggregateRepositoryInterface $repository,
        private readonly SequenceService $sequences,
        private readonly PurchaseTotalsService $totals,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['po_number'] ??= $this->sequences->next(
                'purchase_order',
                (int) $payload['tenant_id'],
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            );
            $payload['status'] = PurchaseOrderStatus::Draft->value;
            $payload['invoice_status'] = 'not_invoiced';
            $payload['lines'] = $this->calculateLines($payload['lines'] ?? [], 'ordered_qty', $payload);
            $payload = array_merge($payload, $this->totals->calculateHeader($payload['lines'], $payload));
            $payload['paid_amount'] = 0;
            $payload['balance'] = $payload['grand_total'];

            $order = $this->repository->createPurchaseOrder($payload);
            event(new PurchaseOrderCreated((int) $order['id']));

            return $order;
        });
    }

    /** @param array<string, mixed> $payload */
    public function update(int $id, array $payload): array
    {
        return DB::transaction(function () use ($id, $payload): array {
            $current = $this->repository->findPurchaseOrderForUpdate($id);
            if ($current === null) {
                throw new \RuntimeException('Purchase order not found.');
            }

            if (($current['status'] ?? null) !== PurchaseOrderStatus::Draft->value) {
                throw new \RuntimeException('Only draft purchase orders can be edited.');
            }

            if (isset($payload['lines']) && is_array($payload['lines'])) {
                $payload['lines'] = $this->calculateLines($payload['lines'], 'ordered_qty', array_merge($current, $payload));
                $payload = array_merge($payload, $this->totals->calculateHeader($payload['lines'], array_merge($current, $payload)));
                $payload['balance'] = round((float) $payload['grand_total'] - (float) ($current['paid_amount'] ?? 0), 4);
            }

            return $this->repository->updatePurchaseOrder($id, $payload, isset($payload['row_version']) ? (int) $payload['row_version'] : null);
        });
    }

    public function confirm(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $order = $this->repository->findPurchaseOrderForUpdate($id);
            if ($order === null) {
                throw new \RuntimeException('Purchase order not found.');
            }

            if (($order['status'] ?? null) !== PurchaseOrderStatus::Draft->value) {
                throw new \RuntimeException('Only draft purchase orders can be confirmed.');
            }

            $confirmed = $this->repository->updatePurchaseOrderHeader($id, [
                'status' => PurchaseOrderStatus::Confirmed->value,
            ]);

            event(new PurchaseOrderConfirmed($id));

            return $confirmed;
        });
    }

    public function cancel(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $order = $this->repository->findPurchaseOrderForUpdate($id);
            if ($order === null) {
                throw new \RuntimeException('Purchase order not found.');
            }

            if (($order['status'] ?? null) === PurchaseOrderStatus::Cancelled->value) {
                return $order;
            }

            if (DB::table('grn_headers')->where('purchase_order_id', $id)->exists()) {
                throw new \RuntimeException('Cannot cancel a purchase order that has GRNs.');
            }

            if (DB::table('invoice_references')->where('document_type', 'PO')->where('document_id', $id)->exists()) {
                throw new \RuntimeException('Cannot cancel a purchase order that has purchase invoices.');
            }

            foreach (($order['lines'] ?? []) as $line) {
                if ((float) ($line['received_qty'] ?? 0) > 0) {
                    throw new \RuntimeException('Cannot cancel a purchase order with received quantities.');
                }
            }

            return $this->repository->updatePurchaseOrderHeader($id, [
                'status' => PurchaseOrderStatus::Cancelled->value,
            ]);
        });
    }

    public function refreshReceiptStatus(int $purchaseOrderId): array
    {
        $order = $this->repository->findPurchaseOrderForUpdate($purchaseOrderId);
        if ($order === null) {
            throw new \RuntimeException('Purchase order not found.');
        }

        $lines = $order['lines'] ?? [];
        $ordered = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['ordered_qty'] ?? 0), $lines));
        $received = (float) array_sum(array_map(static fn (array $line): float => (float) ($line['received_qty'] ?? 0), $lines));

        $status = match (true) {
            $received <= 0 => PurchaseOrderStatus::Confirmed->value,
            $received >= $ordered => PurchaseOrderStatus::Received->value,
            default => PurchaseOrderStatus::PartiallyReceived->value,
        };

        return $this->repository->updatePurchaseOrderHeader($purchaseOrderId, ['status' => $status]);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $header
     * @return array<int, array<string, mixed>>
     */
    private function calculateLines(array $lines, string $quantityColumn, array $header): array
    {
        return array_map(function (array $line) use ($quantityColumn, $header): array {
            $line['tenant_id'] ??= $header['tenant_id'];
            $line['organization_unit_id'] ??= $header['organization_unit_id'] ?? null;
            $line['received_qty'] ??= 0;
            $line['rejected_qty'] ??= 0;
            $line['invoiced_qty'] ??= 0;

            return $this->totals->calculateLine($line, $quantityColumn);
        }, $lines);
    }
}
