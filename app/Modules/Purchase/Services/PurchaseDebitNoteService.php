<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
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
}
