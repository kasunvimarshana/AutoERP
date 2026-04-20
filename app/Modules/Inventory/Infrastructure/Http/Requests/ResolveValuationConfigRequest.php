<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveValuationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'org_unit_id' => 'nullable|integer|exists:org_units,id',
            'transaction_type' => 'nullable|string|max:50',
        ];
    }
}
