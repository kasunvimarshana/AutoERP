<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'       => 'required|integer|exists:tenants,id',
            'uom_category_id' => 'required|integer|exists:uom_categories,id',
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:50',
            'symbol'          => 'required|string|max:20',
            'is_base_unit'    => 'boolean',
            'factor'          => 'numeric|min:0',
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ];
    }
}
