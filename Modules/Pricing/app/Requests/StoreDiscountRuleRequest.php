<?php

declare(strict_types=1);

namespace Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pricing\Enums\DiscountType;

/**
 * Store DiscountRule Request
 */
class StoreDiscountRuleRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:100', 'unique:discount_rules,code'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(DiscountType::values())],
            'value' => ['required', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_products.*' => ['integer', 'exists:products,id'],
            'applicable_categories' => ['nullable', 'array'],
            'applicable_categories.*' => ['integer', 'exists:product_categories,id'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
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
            'max_discount_amount' => 'maximum discount amount',
            'min_purchase_amount' => 'minimum purchase amount',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }
}
