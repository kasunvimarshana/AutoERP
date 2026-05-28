<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'item_id' => [...$required, 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_number' => [...$required, 'string', 'max:100'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
