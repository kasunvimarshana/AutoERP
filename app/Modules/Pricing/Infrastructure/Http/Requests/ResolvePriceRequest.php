<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolvePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'type' => 'required|in:purchase,sales',
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'uom_id' => 'required|integer|exists:units_of_measure,id',
            'quantity' => 'nullable|numeric|gt:0',
            'currency_id' => 'required|integer|exists:currencies,id',
            'customer_id' => 'nullable|integer|exists:customers,id|required_if:type,sales',
            'supplier_id' => 'nullable|integer|exists:suppliers,id|required_if:type,purchase',
            'price_date' => 'nullable|date',
        ];
    }
}
