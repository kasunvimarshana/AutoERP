<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\Enums\CostingMethod;
use Modules\Product\Domain\Enums\ProductType;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = implode(',', array_column(ProductType::cases(), 'value'));
        $methods = implode(',', array_column(CostingMethod::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', "in:{$types}"],
            'uom' => ['required', 'string', 'max:50'],
            'buying_uom' => ['nullable', 'string', 'max:50'],
            'selling_uom' => ['nullable', 'string', 'max:50'],
            'costing_method' => ['required', 'string', "in:{$methods}"],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive,archived'],
        ];
    }
}
