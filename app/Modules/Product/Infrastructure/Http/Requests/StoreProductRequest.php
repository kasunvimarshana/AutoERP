<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\ValueObjects\ProductType;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'                            => 'required|integer|exists:tenants,id',
            'sku'                                  => 'required|string|max:100',
            'name'                                 => 'required|string|max:255',
            'description'                          => 'nullable|string',
            'price'                                => 'required|numeric|min:0',
            'currency'                             => 'nullable|string|size:3',
            'category'                             => 'nullable|string|max:100',
            'status'                               => 'nullable|string|in:active,inactive,draft',
            'type'                                 => 'nullable|string|in:'.implode(',', ProductType::VALID_TYPES),
            'units_of_measure'                     => 'nullable|array',
            'units_of_measure.*.unit'              => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'              => 'required_with:units_of_measure|string|in:buying,selling,inventory',
            'units_of_measure.*.conversion_factor' => 'nullable|numeric|min:0.0001',
            'attributes'                           => 'nullable|array',
            'metadata'                             => 'nullable|array',
            'product_attributes'                   => 'nullable|array',
            'product_attributes.*.code'            => 'required_with:product_attributes|string|max:50',
            'product_attributes.*.name'            => 'required_with:product_attributes|string|max:100',
            'product_attributes.*.allowed_values'  => 'nullable|array',
            'product_attributes.*.allowed_values.*' => 'string|max:100',
            // Optional image uploads (multipart/form-data).
            // Strict validation: JPEG, PNG, GIF, and WebP only; 10 MB max per file.
            'images'                               => 'nullable|array|max:20',
            'images.*'                             => 'file|image|max:10240|mimes:jpeg,png,gif,webp',
            'primary_image'                        => 'nullable|integer|min:0',
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v): void {
            $images       = $this->file('images') ?? [];
            $primaryIndex = $this->input('primary_image');

            if ($primaryIndex !== null && count($images) > 0 && (int) $primaryIndex >= count($images)) {
                $v->errors()->add(
                    'primary_image',
                    'The primary_image index must be less than the number of uploaded images ('.count($images).').'
                );
            }
        });
    }
}
