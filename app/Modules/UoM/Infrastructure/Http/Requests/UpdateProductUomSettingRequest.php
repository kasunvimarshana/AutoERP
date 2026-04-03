<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductUomSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_uom_id'      => 'nullable|integer|exists:units_of_measure,id',
            'purchase_uom_id'  => 'nullable|integer|exists:units_of_measure,id',
            'sales_uom_id'     => 'nullable|integer|exists:units_of_measure,id',
            'inventory_uom_id' => 'nullable|integer|exists:units_of_measure,id',
            'purchase_factor'  => 'numeric|min:0',
            'sales_factor'     => 'numeric|min:0',
            'inventory_factor' => 'numeric|min:0',
            'is_active'        => 'boolean',
        ];
    }
}
