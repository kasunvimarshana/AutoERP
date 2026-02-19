<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductType;

/**
 * Add Bundle Item Request
 */
class AddBundleItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'bundle_id' => $this->route('product')->id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
                'different:bundle_id',
            ],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'product_id.different' => 'A bundle cannot contain itself.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'quantity' => 'quantity',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bundle = $this->route('product');

            if ($bundle->type !== ProductType::BUNDLE) {
                $validator->errors()->add('type', 'Only bundle products can have bundle items.');
            }

            if ($this->product_id) {
                $this->validateNonCircular($validator, $bundle);
            }
        });
    }

    /**
     * Validate that adding this item doesn't create a circular reference
     */
    private function validateNonCircular($validator, $bundle): void
    {
        $productId = $this->product_id;
        $visited = [];
        $toCheck = [$productId];

        while (! empty($toCheck)) {
            $currentId = array_shift($toCheck);

            if ($currentId === $bundle->id) {
                $validator->errors()->add(
                    'product_id',
                    'Adding this product would create a circular reference.'
                );

                return;
            }

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $product = \Modules\Product\Models\Product::find($currentId);
            if ($product && $product->type === ProductType::BUNDLE) {
                $childIds = $product->bundleItems()->pluck('product_id')->toArray();
                $toCheck = array_merge($toCheck, $childIds);
            }
        }
    }
}
