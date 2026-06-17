<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Purchase\DTOs\CreatePurchaseDebitNoteData;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Validators\PurchaseValidationService;

final class PurchaseDebitNoteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseNumberService $numbers,
        private readonly PurchaseValidationService $validator,
        private readonly InvoiceBalanceService $invoiceBalances,
    ) {}

    public function create(CreatePurchaseDebitNoteData $data): PurchaseDebitNote
    {
        $amount = $this->math->normalize($data->amount);
        if ($this->math->isZero($amount) || $this->math->isNegative($amount)) {
            throw new InvalidArgumentException('Purchase debit note amount must be greater than zero.');
        }
        if ($data->supplierId === null) {
            throw new InvalidArgumentException('Purchase debit note supplier is required.');
        }
        if (trim((string) $data->reason) === '') {
            throw new InvalidArgumentException('Purchase debit note reason is required.');
        }

        $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId);

        return PurchaseDebitNote::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'supplier_type' => $data->supplierType,
            'supplier_id' => $data->supplierId,
            'purchase_return_id' => $data->purchaseReturnId,
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'debit_note_number' => $data->debitNoteNumber ?? $this->numbers->next($data->tenantId, 'PDN', 'purchase_debit_notes', 'debit_note_number'),
            'debit_note_date' => $data->debitNoteDate,
            'status' => PurchaseDebitNoteStatus::Draft,
            'amount' => $amount,
            'allocated_amount' => '0.000000',
            'remaining_amount' => $amount,
            'reason' => $data->reason,
        ]);
    }

    public function approve(PurchaseDebitNote $note, ?int $approvedBy = null): PurchaseDebitNote
    {
        if ($note->status !== PurchaseDebitNoteStatus::Draft) {
            throw new InvalidArgumentException('Only draft purchase debit notes can be approved.');
        }

        $note->status = PurchaseDebitNoteStatus::Approved;
        $note->approved_by = $approvedBy;
        $note->approved_at = now();
        $note->save();

        return $note->refresh();
    }

    public function post(PurchaseDebitNote $note): PurchaseDebitNote
    {
        if ($note->status !== PurchaseDebitNoteStatus::Approved) {
            throw new InvalidArgumentException('Only approved purchase debit notes can be posted.');
        }

        $note->status = PurchaseDebitNoteStatus::Posted;
        $note->save();

        return $note->refresh();
    }

    public function allocate(
        PurchaseDebitNote $note,
        Invoice $invoice,
        string $amount,
    ): PurchaseDebitNote {
        return DB::transaction(function () use ($note, $invoice, $amount): PurchaseDebitNote {
            $lockedNote = PurchaseDebitNote::query()
                ->lockForUpdate()
                ->findOrFail($note->getKey());
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());
            $this->assertAllocationScope($lockedNote, $lockedInvoice);
            if (! in_array($lockedNote->status, [
                PurchaseDebitNoteStatus::Posted,
                PurchaseDebitNoteStatus::Allocated,
            ], true)) {
                throw new InvalidArgumentException(
                    'Only posted purchase debit notes can be allocated.',
                );
            }
            if ($this->math->compare($amount, '0.000000') <= 0) {
                throw new InvalidArgumentException('Debit allocation amount must be greater than zero.');
            }
            if ($this->math->compare($amount, (string) $lockedNote->remaining_amount) > 0) {
                throw new InvalidArgumentException(
                    'Debit allocation cannot exceed purchase debit note remaining amount.',
                );
            }

            $this->invoiceBalances->allocateCredit(
                $lockedInvoice,
                'purchase_debit_note',
                (int) $lockedNote->getKey(),
                $amount,
            );
            $lockedNote->allocated_amount = $this->math->add(
                (string) $lockedNote->allocated_amount,
                $amount,
            );
            $lockedNote->remaining_amount = $this->math->sub(
                (string) $lockedNote->amount,
                (string) $lockedNote->allocated_amount,
            );
            $lockedNote->status = $this->math->isZero((string) $lockedNote->remaining_amount)
                ? PurchaseDebitNoteStatus::Allocated
                : PurchaseDebitNoteStatus::Posted;
            $lockedNote->save();

            return $lockedNote->refresh();
        });
    }

    private function assertAllocationScope(PurchaseDebitNote $note, Invoice $invoice): void
    {
        $invoiceStatus = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);
        $noteOrganizationUnitId = $note->organization_unit_id === null
            ? null
            : (int) $note->organization_unit_id;
        $invoiceOrganizationUnitId = $invoice->organization_unit_id === null
            ? null
            : (int) $invoice->organization_unit_id;

        if ((int) $note->tenant_id !== (int) $invoice->tenant_id
            || $noteOrganizationUnitId !== $invoiceOrganizationUnitId
        ) {
            throw new InvalidArgumentException('Purchase debit note and invoice scope does not match.');
        }

        if ((int) $note->supplier_id !== (int) $invoice->party_id
            || (string) $invoice->party_type !== 'supplier'
        ) {
            throw new InvalidArgumentException('Purchase debit note and invoice supplier does not match.');
        }

        if (! in_array($invoiceStatus, [InvoiceStatus::Posted, InvoiceStatus::PartiallyPaid], true)) {
            throw new InvalidArgumentException('Purchase debit notes can only be allocated to posted supplier invoices.');
        }
    }
}
