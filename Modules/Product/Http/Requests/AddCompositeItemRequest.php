<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Enums\ProductType;

/**
 * Add Composite Item Request
 */
class AddCompositeItemRequest extends FormRequest
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
            'composite_id' => $this->route('product')->id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'component_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
                'different:composite_id',
            ],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'component_id.different' => 'A composite cannot contain itself.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'component_id' => 'component product',
            'quantity' => 'quantity',
            'sort_order' => 'sort order',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $composite = $this->route('product');

            if ($composite->type !== ProductType::COMPOSITE) {
                $validator->errors()->add('type', 'Only composite products can have composite items.');
            }

            if ($this->component_id) {
                $this->validateNonCircular($validator, $composite);
            }
        });
    }

    /**
     * Validate that adding this item doesn't create a circular reference
     */
    private function validateNonCircular($validator, $composite): void
    {
        $componentId = $this->component_id;
        $visited = [];
        $toCheck = [$componentId];

        while (! empty($toCheck)) {
            $currentId = array_shift($toCheck);

            if ($currentId === $composite->id) {
                $validator->errors()->add(
                    'component_id',
                    'Adding this product would create a circular reference.'
                );

                return;
            }

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $product = \Modules\Product\Models\Product::find($currentId);
            if ($product && $product->type === ProductType::COMPOSITE) {
                $childIds = $product->compositeParts()->pluck('component_id')->toArray();
                $toCheck = array_merge($toCheck, $childIds);
            }
        }
    }
}
