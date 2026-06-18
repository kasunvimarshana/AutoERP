<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;

final class ListPurchaseDocumentRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_values(array_unique(array_map(
                static fn (\BackedEnum $status): string => (string) $status->value,
                [
                    ...PurchaseOrderStatus::cases(),
                    ...GoodsReceiptNoteStatus::cases(),
                    ...PurchaseReturnStatus::cases(),
                    ...PurchaseDebitNoteStatus::cases(),
                ],
            ))))],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'receipt_status' => ['nullable', Rule::in(['not_received', 'partially_received', 'received'])],
            'invoice_status' => ['nullable', Rule::in(['not_invoiced', 'partially_invoiced', 'invoiced'])],
            'return_status' => ['nullable', Rule::in(['not_returned', 'partially_returned', 'returned'])],
            'allocation_status' => ['nullable', Rule::in(['unallocated', 'partially_allocated', 'allocated'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }
}
