<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTransferOrderRequest extends FormRequest
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
            'from_warehouse_id' => [...$required, 'integer', 'min:1'],
            'to_warehouse_id' => [...$required, 'integer', 'min:1'],
            'transfer_number' => [...$required, 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:DRAFT,PENDING,COMPLETED,CANCELLED'],
            'request_date' => [...$required, 'date'],
            'expected_date' => ['nullable', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'row_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
