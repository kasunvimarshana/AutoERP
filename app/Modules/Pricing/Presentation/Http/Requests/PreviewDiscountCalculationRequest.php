<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class PreviewDiscountCalculationRequest extends FormRequest
{
    use ResolvesPricingTenant;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discounts' => ['nullable', 'array'],
            'discounts.*.id' => ['nullable', 'integer', 'min:1'],
            'discounts.*.code' => ['nullable', 'string', 'max:255'],
            'discounts.*.name' => ['nullable', 'string', 'max:255'],
            'discounts.*.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discounts.*.discount_value' => ['required_with:discounts', 'numeric', 'min:0'],
            'discounts.*.min_quantity' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.max_quantity' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.priority' => ['nullable', 'integer', 'min:0'],
            'discounts.*.is_stackable' => ['nullable', 'boolean'],
            'discounts.*.is_exclusive' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $discounts = $this->input('discounts');
            $hasDiscountList = is_array($discounts) && $discounts !== [];
            $hasSingleDiscount = $this->filled('discount_value');

            if (! $hasDiscountList && ! $hasSingleDiscount) {
                $validator->errors()->add(
                    'discounts',
                    'Provide either discounts or discount_type and discount_value for the preview.',
                );
            }

            foreach ((array) $discounts as $index => $discount) {
                if (! is_array($discount)) {
                    continue;
                }

                if (
                    isset($discount['min_quantity'], $discount['max_quantity'])
                    && (float) $discount['max_quantity'] < (float) $discount['min_quantity']
                ) {
                    $validator->errors()->add(
                        "discounts.$index.max_quantity",
                        'The max quantity must be greater than or equal to the min quantity.',
                    );
                }
            }
        });
    }
}
