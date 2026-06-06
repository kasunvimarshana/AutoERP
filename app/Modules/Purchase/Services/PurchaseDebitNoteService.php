<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\PurchaseDebitNoteData;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Models\PurchaseDebitNote;

final class PurchaseDebitNoteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseNumberService $numbers,
    ) {}

    public function create(PurchaseDebitNoteData $data): PurchaseDebitNote
    {
        $amount = $this->math->normalize($data->amount);

        return PurchaseDebitNote::query()->create([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'supplier_type' => $data->supplierType,
            'supplier_id' => $data->supplierId,
            'purchase_return_id' => $data->purchaseReturnId,
            'debit_note_number' => $data->debitNoteNumber ?? $this->numbers->next($data->tenantId, 'PDN', 'purchase_debit_notes', 'debit_note_number'),
            'debit_note_date' => $data->debitNoteDate,
            'status' => PurchaseDebitNoteStatus::Draft,
            'amount' => $amount,
            'allocated_amount' => '0.000000',
            'remaining_amount' => $amount,
            'reason' => $data->reason,
        ]);
    }
}
