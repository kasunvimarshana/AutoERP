<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Sales\DTOs\SalesCreditNoteData;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Validators\SalesValidationService;

final class SalesCreditNoteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesNumberService $numbers,
        private readonly SalesValidationService $validator,
        private readonly InvoiceBalanceService $invoiceBalances,
    ) {}

    public function create(SalesCreditNoteData $data): SalesCreditNote
    {
        $amount = $this->math->normalize($data->amount);
        if ($this->math->compare($amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Sales credit note amount must be greater than zero.');
        }
        if (trim((string) $data->reason) === '') {
            throw new InvalidArgumentException('Sales credit note reason is required.');
        }
        $this->validator->customer($data->tenantId, $data->organizationUnitId, $data->customerId);

        return SalesCreditNote::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'customer_id' => $data->customerId,
            'sales_return_id' => $data->salesReturnId,
            'credit_note_number' => $data->creditNoteNumber ?? $this->numbers->next(
                $data->tenantId,
                $data->organizationUnitId,
                'credit_note',
                $data->creditNoteDate,
                'SCN',
            ),
            'credit_note_date' => $data->creditNoteDate,
            'status' => SalesCreditNoteStatus::Draft,
            'amount' => $amount,
            'allocated_amount' => '0.000000',
            'remaining_amount' => $amount,
            'reason' => $data->reason,
        ]);
    }

    public function allocate(SalesCreditNote $note, Invoice $invoice, string $amount): SalesCreditNote
    {
        return DB::transaction(function () use ($note, $invoice, $amount): SalesCreditNote {
            $lockedNote = SalesCreditNote::query()
                ->lockForUpdate()
                ->findOrFail($note->getKey());
            $this->assertAllocationScope($lockedNote, $invoice);
            if (! in_array($lockedNote->status, [
                SalesCreditNoteStatus::Posted,
                SalesCreditNoteStatus::Allocated,
            ], true)) {
                throw new InvalidArgumentException(
                    'Only posted sales credit notes can be allocated.',
                );
            }

            if ($this->math->compare($amount, (string) $lockedNote->remaining_amount) > 0) {
                throw new InvalidArgumentException(
                    'Credit allocation cannot exceed sales credit note remaining amount.',
                );
            }

            $this->invoiceBalances->allocateCredit(
                $invoice,
                'sales_credit_note',
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
                ? SalesCreditNoteStatus::Allocated
                : SalesCreditNoteStatus::Posted;
            $lockedNote->save();

            return $lockedNote->refresh();
        });
    }

    public function approve(SalesCreditNote $note): SalesCreditNote
    {
        if ($note->status !== SalesCreditNoteStatus::Draft) {
            throw new InvalidArgumentException('Only draft sales credit notes can be approved.');
        }

        $note->status = SalesCreditNoteStatus::Approved;
        $note->save();

        return $note->refresh();
    }

    public function post(SalesCreditNote $note): SalesCreditNote
    {
        if ($note->status !== SalesCreditNoteStatus::Approved) {
            throw new InvalidArgumentException('Only approved sales credit notes can be posted.');
        }

        $note->status = SalesCreditNoteStatus::Posted;
        $note->save();

        return $note->refresh();
    }

    private function assertAllocationScope(SalesCreditNote $note, Invoice $invoice): void
    {
        if ((int) $note->tenant_id !== (int) $invoice->tenant_id
            || $note->organization_unit_id !== $invoice->organization_unit_id
            || (int) $note->customer_id !== (int) $invoice->party_id
            || $invoice->party_type !== 'customer'
        ) {
            throw new InvalidArgumentException(
                'Sales credit note and invoice scope or customer does not match.',
            );
        }
    }
}
