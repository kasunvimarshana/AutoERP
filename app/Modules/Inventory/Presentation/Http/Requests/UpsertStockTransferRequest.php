<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Inventory\Presentation\Http\Requests\Concerns\ValidatesInventoryItemUoms;

final class UpsertStockTransferRequest extends FormRequest
{
    use ValidatesInventoryItemUoms;

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
            'reference_number' => [...$required, 'string', 'max:100'],
            'from_warehouse_id' => [...$required, 'integer', 'min:1', 'different:to_warehouse_id'],
            'to_warehouse_id' => [...$required, 'integer', 'min:1'],
            'from_location_id' => ['nullable', 'integer', 'min:1'],
            'to_location_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'requested_by' => [...$required, 'integer', 'min:1'],
            'approved_by' => ['nullable', 'integer', 'min:1'],
            'transferred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['sometimes', 'array'],
            'lines.*.item_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.from_location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.to_location_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = (int) $this->input('tenant_id');
            foreach ((array) $this->input('lines', []) as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $this->addItemUomErrorWhenInvalid(
                    $validator,
                    $tenantId,
                    $line['item_id'] ?? null,
                    $line['uom_id'] ?? null,
                    'lines.'.$index.'.uom_id',
                );
            }
        });
    }
}
