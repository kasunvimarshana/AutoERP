<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSupplierTaxProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tax_identifier' => ['nullable', 'string', 'max:120'],
            'vat_identifier' => ['nullable', 'string', 'max:120'],
            'tax_type' => ['nullable', 'string', 'max:80'],
            'withholding_rate' => ['nullable', 'numeric', 'min:0'],
            'is_tax_exempt' => ['nullable', 'boolean'],
            'tax_exempt_until' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
