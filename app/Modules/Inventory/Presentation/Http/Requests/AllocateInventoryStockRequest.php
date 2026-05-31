<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Inventory\Presentation\Http\Requests\Concerns\ValidatesInventoryItemUoms;

final class AllocateInventoryStockRequest extends FormRequest
{
    use ValidatesInventoryItemUoms;

    protected function prepareForValidation(): void
    {
        $tenantId = $this->input('tenant_id') ?: $this->attributes->get('current_tenant_id');
        $organizationUnitId = $this->input('organization_unit_id') ?: $this->attributes->get('current_organization_unit_id');

        $this->merge(array_filter([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['sometimes', 'nullable', 'integer'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer', 'exists:warehouses,id'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:warehouse_locations,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'variant_id' => ['sometimes', 'nullable', 'integer'],
            'batch_id' => ['sometimes', 'nullable', 'integer', 'exists:batches,id'],
            'serial_id' => ['sometimes', 'nullable', 'integer', 'exists:serials,id'],
            'uom_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'lot_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'allocation_method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'source_module' => ['sometimes', 'nullable', 'string', 'max:100'],
            'source_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'source_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'source_reference' => ['sometimes', 'nullable', 'string', 'max:150'],
            'dimensions' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->addItemUomErrorWhenInvalid(
                $validator,
                (int) $this->input('tenant_id'),
                $this->input('item_id'),
                $this->input('uom_id'),
                'uom_id',
            );
        });
    }
}
