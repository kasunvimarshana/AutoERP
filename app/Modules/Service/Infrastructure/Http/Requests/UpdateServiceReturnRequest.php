<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_type' => ['sometimes', 'string', 'in:inventory_return,customer_refund,supplier_return'],
            'status' => ['sometimes', 'string', 'in:draft,approved,completed,cancelled'],
            'reason_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'processed_by' => ['sometimes', 'integer', 'min:1'],
            'currency_id' => ['sometimes', 'integer', 'min:1'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'journal_entry_id' => ['sometimes', 'integer', 'min:1'],
            'payment_id' => ['sometimes', 'integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
