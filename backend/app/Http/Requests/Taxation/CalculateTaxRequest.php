<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxation;

use Illuminate\Foundation\Http\FormRequest;

class CalculateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'branch_id' => ['nullable', 'integer'],
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
            'is_inclusive' => ['boolean'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be greater than or equal to 0',
            'product_id.exists' => 'Selected product does not exist',
            'customer_id.exists' => 'Selected customer does not exist',
            'tax_rate_id.exists' => 'Selected tax rate does not exist',
            'tax_group_id.exists' => 'Selected tax group does not exist',
            'country_code.size' => 'Country code must be 2 characters',
        ];
    }
}
