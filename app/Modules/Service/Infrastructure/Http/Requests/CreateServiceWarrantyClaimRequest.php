<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceWarrantyClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warranty_provider' => ['required', 'string', 'max:255'],
            'supplier_id' => ['sometimes', 'integer', 'min:1'],
            'claim_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'currency_id' => ['sometimes', 'integer', 'min:1'],
            'claim_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
