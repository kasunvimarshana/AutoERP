<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceWarrantyClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warranty_provider' => ['sometimes', 'string', 'max:255'],
            'supplier_id' => ['sometimes', 'integer', 'min:1'],
            'claim_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:draft,submitted,approved,rejected,settled'],
            'currency_id' => ['sometimes', 'integer', 'min:1'],
            'claim_amount' => ['sometimes', 'numeric', 'min:0'],
            'approved_amount' => ['sometimes', 'numeric', 'min:0'],
            'received_amount' => ['sometimes', 'numeric', 'min:0'],
            'journal_entry_id' => ['sometimes', 'integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
