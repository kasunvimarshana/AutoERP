<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVehicleServiceLaborItemRequest extends FormRequest
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
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'reference' => ['nullable', 'string', 'max:255'],
            'job_card_id' => array_merge($required, ['integer', 'min:1', 'exists:vehicle_service_job_cards,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'combo_item_id' => ['nullable', 'integer', 'min:1', 'exists:combo_items,id'],
            'description' => ['nullable', 'string'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'quantity' => array_merge($required, ['numeric', 'gt:0']),
            'unit_price' => array_merge($required, ['numeric', 'min:0']),
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'incentive_type' => ['nullable', 'in:percentage,fixed'],
            'incentive_value' => ['nullable', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
        ];
    }
}
