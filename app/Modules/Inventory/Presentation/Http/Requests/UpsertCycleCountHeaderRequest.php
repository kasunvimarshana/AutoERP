<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCycleCountHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'warehouse_id' => [...$required, 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'counted_by_user_id' => ['nullable', 'integer', 'min:1'],
            'counted_at' => ['nullable', 'date'],
            'approved_by_user_id' => ['nullable', 'integer', 'min:1'],
            'approved_at' => ['nullable', 'date'],
            'lines' => ['sometimes', 'array'],
            'lines.*.item_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.system_qty' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.counted_qty' => ['required_with:lines', 'numeric', 'gte:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.variance_reason' => ['nullable', 'string', 'max:255'],
            'lines.*.counted_by_user_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }
}
