<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUomConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'from_uom_id' => 'required|integer|exists:units_of_measure,id',
            'to_uom_id'   => 'required|integer|exists:units_of_measure,id',
            'factor'      => 'required|numeric|min:0',
            'is_active'   => 'boolean',
        ];
    }
}
