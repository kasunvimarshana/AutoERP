<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Services\JournalEntryService;
use Modules\Purchase\Domain\Enums\AdvancePaymentStatus;
use Modules\Purchase\Domain\Enums\DocumentReferenceType;
use Modules\Sequence\Domain\Services\SequenceService;

final class PurchaseAdvancePaymentService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly JournalEntryService $journals,
    )
    {
    }

    /** @param array<string, mixed> $payload */
    public function record(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $id = (int) DB::table('advance_payments')->insertGetId([
                'tenant_id' => (int) $payload['tenant_id'],
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'metadata' => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
                'party_type' => 'supplier',
                'party_id' => (int) $payload['party_id'],
                'reference' => $payload['reference'] ?? null,
                'advance_number' => $payload['advance_number'] ?? $this->sequences->next(
                    'supplier_advance',
                    (int) $payload['tenant_id'],
                    isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
                ),
                'amount' => (float) $payload['amount'],
                'remaining_amount' => (float) $payload['amount'],
                'advance_date' => $payload['advance_date'],
                'type' => 'supplier',
                'status' => AdvancePaymentStatus::Open->value,
                'payment_id' => $payload['payment_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'created_by' => $payload['created_by'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $advance = (array) DB::table('advance_payments')->find($id);
            if (! empty($advance['payment_id'])) {
                $journalEntryId = $this->journals->createAdvancePaymentEntry($advance);
                $metadata = is_array($advance['metadata'] ?? null) ? $advance['metadata'] : [];
                DB::table('advance_payments')->where('id', $id)->update([
                    'metadata' => json_encode(array_merge($metadata, ['journal_entry_id' => $journalEntryId])),
                    'updated_at' => now(),
                ]);
            }

            return (array) DB::table('advance_payments')->find($id);
        });
    }

    /** @param array<string, mixed> $payload */
    public function allocate(int $advancePaymentId, array $payload): array
    {
        return DB::transaction(function () use ($advancePaymentId, $payload): array {
            $advance = DB::table('advance_payments')->lockForUpdate()->find($advancePaymentId);
            if ($advance === null) {
                throw new \RuntimeException('Advance payment not found.');
            }

            $amount = (float) $payload['allocated_amount'];
            if ($amount > (float) $advance->remaining_amount) {
                throw new \RuntimeException('Allocation exceeds remaining advance amount.');
            }

            DB::table('advance_payment_allocations')->insert([
                'tenant_id' => (int) $advance->tenant_id,
                'organization_unit_id' => $advance->organization_unit_id,
                'advance_payment_id' => $advancePaymentId,
                'document_type' => (string) $payload['document_type'],
                'document_id' => (int) $payload['document_id'],
                'reference' => $payload['reference'] ?? null,
                'allocated_amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ((string) $payload['document_type'] === DocumentReferenceType::Invoice->value) {
                $this->allocateToInvoice((int) $payload['document_id'], $amount);
            }

            $remaining = round((float) $advance->remaining_amount - $amount, 4);
            DB::table('advance_payments')->where('id', $advancePaymentId)->update([
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0
                    ? AdvancePaymentStatus::FullyApplied->value
                    : AdvancePaymentStatus::PartiallyApplied->value,
                'row_version' => (int) $advance->row_version + 1,
                'updated_at' => now(),
            ]);

            return (array) DB::table('advance_payments')->find($advancePaymentId);
        });
    }

    private function allocateToInvoice(int $invoiceId, float $amount): void
    {
        $invoice = DB::table('invoices')->lockForUpdate()->find($invoiceId);
        if ($invoice === null) {
            throw new \RuntimeException('Invoice not found for advance allocation.');
        }

        if ($invoice->direction !== 'inbound') {
            throw new \RuntimeException('Supplier advances can only be applied to inbound invoices.');
        }

        if ($amount > (float) $invoice->balance) {
            throw new \RuntimeException('Advance allocation exceeds invoice balance.');
        }

        $paid = round((float) $invoice->paid_amount + $amount, 4);
        $balance = round((float) $invoice->grand_total - $paid, 4);

        DB::table('invoices')->where('id', $invoiceId)->update([
            'paid_amount' => $paid,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : 'partially_paid',
            'row_version' => (int) $invoice->row_version + 1,
            'updated_at' => now(),
        ]);
    }
}
