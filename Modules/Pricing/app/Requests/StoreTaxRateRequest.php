<?php

declare(strict_types=1);

namespace Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store TaxRate Request
 */
class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:tax_rates,code'],
            'description' => ['nullable', 'string'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'product_category' => ['nullable', 'string', 'max:255'],
            'is_compound' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
            'effective_date' => 'effective date',
            'expiry_date' => 'expiry date',
        ];
    }
}
