<?php

declare(strict_types=1);

namespace Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pricing\Enums\PriceListStatus;

/**
 * Update PriceList Request
 */
class UpdatePriceListRequest extends FormRequest
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
        $priceListId = $this->route('priceList') ?? $this->route('id');

        return [
            'branch_id' => ['sometimes', 'nullable', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:100', Rule::unique('price_lists', 'code')->ignore($priceListId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(PriceListStatus::values())],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'customer_id' => ['sometimes', 'nullable', 'exists:customers,id'],
            'location_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'customer_group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
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
}
