<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Domain\Enums\CostingMethod;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $methods = implode(',', array_column(CostingMethod::cases(), 'value'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'uom' => ['required', 'string', 'max:50'],
            'buying_uom' => ['nullable', 'string', 'max:50'],
            'selling_uom' => ['nullable', 'string', 'max:50'],
            'costing_method' => ['required', 'string', "in:{$methods}"],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
        ];
    }
}
