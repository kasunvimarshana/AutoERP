<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'integer'],
            'account_id' => ['sometimes', 'required', 'integer', 'exists:accounts,id'],
            'description' => ['sometimes', 'required', 'string'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
