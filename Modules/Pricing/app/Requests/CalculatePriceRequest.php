<?php

declare(strict_types=1);

namespace Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pricing\Enums\PriceType;

/**
 * Calculate Price Request
 */
class CalculatePriceRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'strategy' => ['sometimes', Rule::in(PriceType::values())],
            'currency' => ['sometimes', 'string', 'size:3'],
            'customer_id' => ['sometimes', 'nullable', 'integer', 'exists:customers,id'],
            'customer_group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'location_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'price_list_id' => ['sometimes', 'nullable', 'integer', 'exists:price_lists,id'],
            'markup_percentage' => ['sometimes', 'numeric', 'min:0'],
            'discount_code' => ['sometimes', 'string', 'max:100'],
            'apply_auto_discounts' => ['sometimes', 'boolean'],
            'calculate_tax' => ['sometimes', 'boolean'],
            'jurisdiction' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_category' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'customer_id' => 'customer',
            'price_list_id' => 'price list',
        ];
    }
}
