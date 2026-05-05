<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_number' => ['sometimes', 'string', 'max:100'],
            'return_type' => ['sometimes', 'string', 'in:inventory_return,customer_refund,supplier_return'],
            'reason_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'currency_id' => ['sometimes', 'integer', 'min:1'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
