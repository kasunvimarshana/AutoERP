<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Inventory\Domain\Services\InventoryTransactionService;
use Modules\Purchase\Domain\Enums\PurchaseReturnStatus;
use Modules\Purchase\Domain\Events\PurchaseReturnProcessed;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;
use Modules\Sequence\Domain\Services\SequenceService;

final class PurchaseReturnService
{
    public function __construct(
        private readonly PurchaseAggregateRepositoryInterface $repository,
        private readonly SequenceService $sequences,
        private readonly PurchaseTotalsService $totals,
        private readonly InventoryTransactionService $inventory,
        private readonly JournalEntryService $journals,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $payload['return_number'] ??= $this->sequences->next(
                'purchase_return',
                (int) $payload['tenant_id'],
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            );
            $payload['status'] = PurchaseReturnStatus::Draft->value;
            $payload['lines'] = $this->calculateLines($payload['lines'] ?? [], $payload);
            $payload = array_merge($payload, $this->totals->calculateHeader($payload['lines'], $payload, true));

            return $this->repository->createPurchaseReturn($payload);
        });
    }

    public function approve(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $purchaseReturn = $this->repository->findPurchaseReturnForUpdate($id);
            if ($purchaseReturn === null) {
                throw new \RuntimeException('Purchase return not found.');
            }

            if (($purchaseReturn['status'] ?? null) === PurchaseReturnStatus::Approved->value) {
                return $purchaseReturn;
            }

            foreach (($purchaseReturn['lines'] ?? []) as $line) {
                $this->inventory->issue(
                    (int) $line['item_id'],
                    (float) $line['return_qty'],
                    (float) $line['unit_price'],
                    (int) $line['warehouse_id'],
                    'PURCHASE_RETURN',
                    (int) $line['id'],
                    array_merge($line, ['reference_type' => 'PURCHASE_RETURN']),
                );
            }

            $journalEntryId = $this->journals->createPurchaseReturnEntry($purchaseReturn);
            $creditNoteId = $this->createCreditNote($purchaseReturn, $journalEntryId);
            $this->refreshRelatedPurchaseOrder($purchaseReturn);
            $approved = $this->repository->updatePurchaseReturnHeader($id, [
                'status' => PurchaseReturnStatus::Approved->value,
                'metadata' => array_merge(
                    is_array($purchaseReturn['metadata'] ?? null) ? $purchaseReturn['metadata'] : [],
                    ['journal_entry_id' => $journalEntryId, 'credit_note_invoice_id' => $creditNoteId],
                ),
            ]);

            event(new PurchaseReturnProcessed($id));

            return $approved;
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
            $line['restocking_fee'] ??= 0;

            return $this->totals->calculateLine($line, 'return_qty');
        }, $lines);
    }

    /** @param array<string, mixed> $purchaseReturn */
    private function createCreditNote(array $purchaseReturn, int $journalEntryId): int
    {
        $existing = DB::table('invoice_references')
            ->where('tenant_id', (int) $purchaseReturn['tenant_id'])
            ->where('document_type', 'PURCHASE_RETURN')
            ->where('document_id', (int) $purchaseReturn['id'])
            ->value('invoice_id');
        if ($existing !== null) {
            return (int) $existing;
        }

        $tenantId = (int) $purchaseReturn['tenant_id'];
        $organizationUnitId = $purchaseReturn['organization_unit_id'] ?? null;
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'metadata' => json_encode(['source' => 'purchase_return']),
            'direction' => 'inbound',
            'invoice_type' => 'credit_note',
            'invoice_number' => $this->sequences->next('purchase_credit_note', $tenantId, $organizationUnitId === null ? null : (int) $organizationUnitId),
            'reference' => $purchaseReturn['return_number'] ?? null,
            'status' => 'approved',
            'party_type' => 'supplier',
            'party_id' => (int) $purchaseReturn['supplier_id'],
            'invoice_date' => $purchaseReturn['return_date'],
            'due_date' => $purchaseReturn['return_date'],
            'currency_id' => $purchaseReturn['currency_id'] ?? null,
            'exchange_rate' => $purchaseReturn['exchange_rate'] ?? 1,
            'subtotal' => $purchaseReturn['subtotal'],
            'line_tax_total' => $purchaseReturn['line_tax_total'],
            'line_discount_total' => $purchaseReturn['line_discount_total'],
            'header_discount_type' => $purchaseReturn['header_discount_type'] ?? null,
            'header_discount_value' => $purchaseReturn['header_discount_value'] ?? null,
            'header_discount_amount' => $purchaseReturn['header_discount_amount'],
            'header_tax_group_id' => $purchaseReturn['header_tax_group_id'] ?? null,
            'header_tax_amount' => $purchaseReturn['header_tax_amount'],
            'discount_total' => $purchaseReturn['discount_total'],
            'tax_total' => $purchaseReturn['tax_total'],
            'debit_note_total' => 0,
            'credit_note_total' => $purchaseReturn['grand_total'],
            'grand_total' => $purchaseReturn['grand_total'],
            'paid_amount' => 0,
            'balance' => $purchaseReturn['grand_total'],
            'ap_account_id' => DB::table('suppliers')->where('id', (int) $purchaseReturn['supplier_id'])->value('ap_account_id'),
            'journal_entry_id' => $journalEntryId,
            'notes' => $purchaseReturn['notes'] ?? null,
            'created_by' => $purchaseReturn['created_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_references')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_id' => $invoiceId,
            'document_type' => 'PURCHASE_RETURN',
            'document_id' => (int) $purchaseReturn['id'],
            'currency_id' => $purchaseReturn['currency_id'] ?? null,
            'exchange_rate' => $purchaseReturn['exchange_rate'] ?? 1,
            'subtotal' => $purchaseReturn['subtotal'],
            'line_tax_total' => $purchaseReturn['line_tax_total'],
            'line_discount_total' => $purchaseReturn['line_discount_total'],
            'header_discount_type' => $purchaseReturn['header_discount_type'] ?? null,
            'header_discount_value' => $purchaseReturn['header_discount_value'] ?? null,
            'header_discount_amount' => $purchaseReturn['header_discount_amount'],
            'header_tax_group_id' => $purchaseReturn['header_tax_group_id'] ?? null,
            'header_tax_amount' => $purchaseReturn['header_tax_amount'],
            'discount_total' => $purchaseReturn['discount_total'],
            'tax_total' => $purchaseReturn['tax_total'],
            'grand_total' => $purchaseReturn['grand_total'],
            'balance' => $purchaseReturn['grand_total'],
            'ap_account_id' => DB::table('suppliers')->where('id', (int) $purchaseReturn['supplier_id'])->value('ap_account_id'),
            'journal_entry_id' => $journalEntryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (($purchaseReturn['lines'] ?? []) as $line) {
            DB::table('invoice_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'invoice_id' => $invoiceId,
                'reference' => 'PURCHASE_RETURN_LINE:' . $line['id'],
                'item_type' => 'item',
                'item_id' => $line['item_id'],
                'description' => $line['description'] ?? null,
                'uom_id' => $line['uom_id'],
                'quantity' => $line['return_qty'],
                'unit_price' => $line['unit_price'],
                'discount_type' => $line['discount_type'] ?? null,
                'discount_value' => $line['discount_value'] ?? 0,
                'discount_amount' => $line['discount_amount'] ?? 0,
                'gross_amount' => $line['gross_amount'] ?? 0,
                'line_total' => $line['line_total'] ?? 0,
                'tax_group_id' => $line['tax_group_id'] ?? null,
                'tax_amount' => $line['tax_amount'] ?? 0,
                'line_total_with_tax' => $line['line_total_with_tax'] ?? 0,
                'account_id' => $line['account_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $invoiceId;
    }

    /** @param array<string, mixed> $purchaseReturn */
    private function refreshRelatedPurchaseOrder(array $purchaseReturn): void
    {
        $purchaseOrderId = $purchaseReturn['original_purchase_order_id'] ?? null;
        if ($purchaseOrderId === null && ! empty($purchaseReturn['original_grn_id'])) {
            $purchaseOrderId = DB::table('grn_headers')
                ->where('id', (int) $purchaseReturn['original_grn_id'])
                ->value('purchase_order_id');
        }

        if ($purchaseOrderId === null) {
            return;
        }

        $returnIds = DB::table('purchase_returns')
            ->where(function ($query) use ($purchaseReturn): void {
                $query->where('status', PurchaseReturnStatus::Approved->value)
                    ->orWhere('id', (int) $purchaseReturn['id']);
            })
            ->where(function ($query) use ($purchaseOrderId): void {
                $query->where('original_purchase_order_id', (int) $purchaseOrderId)
                    ->orWhereIn('original_grn_id', function ($subQuery) use ($purchaseOrderId): void {
                        $subQuery->select('id')
                            ->from('grn_headers')
                            ->where('purchase_order_id', (int) $purchaseOrderId);
                    });
            })
            ->pluck('id');

        $creditNoteTotal = (float) DB::table('purchase_returns')
            ->whereIn('id', $returnIds)
            ->sum('grand_total');
        $order = DB::table('purchase_orders')->lockForUpdate()->find((int) $purchaseOrderId);
        if ($order === null) {
            return;
        }

        DB::table('purchase_orders')->where('id', (int) $purchaseOrderId)->update([
            'credit_note_total' => round($creditNoteTotal, 4),
            'balance' => round((float) $order->grand_total - (float) $order->paid_amount - $creditNoteTotal, 4),
            'row_version' => (int) $order->row_version + 1,
            'updated_at' => now(),
        ]);
    }
}
