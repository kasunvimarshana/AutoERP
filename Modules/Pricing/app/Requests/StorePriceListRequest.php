<?php

declare(strict_types=1);

namespace Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pricing\Enums\PriceListStatus;

/**
 * Store PriceList Request
 *
 * Validates data for creating a new price list
 */
class StorePriceListRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:100', 'unique:price_lists,code'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(PriceListStatus::values())],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'location_code' => ['nullable', 'string', 'max:50'],
            'customer_group' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
            'customer_id' => 'customer',
            'currency_code' => 'currency',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'The price list code has already been taken.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
