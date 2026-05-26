<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Inventory\Domain\Services\InventoryTransactionService;
use Modules\Purchase\Domain\Enums\GrnStatus;
use Modules\Purchase\Domain\Enums\PurchaseOrderStatus;
use Modules\Purchase\Domain\Events\GrnConfirmed;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;
use Modules\Sequence\Domain\Services\SequenceService;

final class GoodsReceiptService
{
    public function __construct(
        private readonly PurchaseAggregateRepositoryInterface $repository,
        private readonly SequenceService $sequences,
        private readonly PurchaseTotalsService $totals,
        private readonly InventoryTransactionService $inventory,
        private readonly JournalEntryService $journals,
        private readonly PurchaseOrderService $purchaseOrders,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            if (! empty($payload['purchase_order_id'])) {
                $po = $this->repository->findPurchaseOrderForUpdate((int) $payload['purchase_order_id']);
                if ($po === null || ($po['status'] ?? null) === PurchaseOrderStatus::Draft->value) {
                    throw new \RuntimeException('GRN requires a confirmed purchase order.');
                }
            }

            $payload['grn_number'] ??= $this->sequences->next(
                'grn',
                (int) $payload['tenant_id'],
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            );
            $payload['status'] = GrnStatus::Draft->value;
            $payload['invoice_status'] = 'not_invoiced';
            $payload['lines'] = $this->calculateLines($payload['lines'] ?? [], $payload);
            $payload = array_merge($payload, $this->totals->calculateHeader($payload['lines'], $payload));

            return $this->repository->createGrn($payload);
        });
    }

    public function confirm(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $grn = $this->repository->findGrnForUpdate($id);
            if ($grn === null) {
                throw new \RuntimeException('GRN not found.');
            }

            if (($grn['status'] ?? null) === GrnStatus::Confirmed->value) {
                return $grn;
            }

            foreach (($grn['lines'] ?? []) as $line) {
                if ((float) ($line['received_qty'] ?? 0) < 0 || (float) ($line['rejected_qty'] ?? 0) < 0) {
                    throw new \RuntimeException('GRN quantities cannot be negative.');
                }

                if (! empty($line['purchase_order_line_id'])) {
                    $poLine = DB::table('purchase_order_lines')->lockForUpdate()->find((int) $line['purchase_order_line_id']);
                    if ($poLine === null) {
                        throw new \RuntimeException('Referenced purchase order line not found.');
                    }

                    $newReceived = (float) $poLine->received_qty + (float) $line['received_qty'];
                    $newRejected = (float) $poLine->rejected_qty + (float) $line['rejected_qty'];
                    if ($newReceived + $newRejected > (float) $poLine->ordered_qty) {
                        throw new \RuntimeException('GRN quantity exceeds ordered quantity.');
                    }

                    $this->repository->updatePurchaseOrderLine((int) $poLine->id, [
                        'received_qty' => $newReceived,
                        'rejected_qty' => $newRejected,
                    ]);
                }

                if ((float) $line['received_qty'] > 0) {
                    $this->inventory->receive(
                        (int) $line['item_id'],
                        (float) $line['received_qty'],
                        (float) $line['unit_price'],
                        (int) ($line['warehouse_id'] ?? $grn['warehouse_id']),
                        'GRN',
                        (int) $line['id'],
                        $line,
                    );
                }
            }

            $journalEntryId = $this->journals->createPurchaseAccrual($grn);
            $confirmed = $this->repository->updateGrnHeader($id, ['status' => GrnStatus::Confirmed->value]);
            $confirmed = $this->repository->updateGrnHeader($id, [
                'metadata' => array_merge($confirmed['metadata'] ?? [], ['journal_entry_id' => $journalEntryId]),
            ]);

            if (! empty($confirmed['purchase_order_id'])) {
                $this->purchaseOrders->refreshReceiptStatus((int) $confirmed['purchase_order_id']);
            }

            event(new GrnConfirmed($id));

            return $confirmed;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $header
     * @return array<int, array<string, mixed>>
     */
    private function calculateLines(array $lines, array $header): array
    {
        return array_map(function (array $line) use ($header): array {
            $line['tenant_id'] ??= $header['tenant_id'];
            $line['organization_unit_id'] ??= $header['organization_unit_id'] ?? null;
            $line['warehouse_id'] ??= $header['warehouse_id'];
            $line['expected_qty'] ??= $line['received_qty'] ?? 0;
            $line['rejected_qty'] ??= 0;
            $line['invoiced_qty'] ??= 0;

            return $this->totals->calculateLine($line, 'received_qty');
        }, $lines);
    }
}
