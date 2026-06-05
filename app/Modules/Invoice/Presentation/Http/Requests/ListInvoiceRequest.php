<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['draft', 'issued', 'partially_paid', 'paid', 'cancelled', 'credited'])],
            'document_type' => ['sometimes', Rule::in(['invoice', 'purchase_invoice', 'debit_adjustment', 'credit_adjustment', 'refund', 'reversal', 'write_off'])],
        ];
    }
}
