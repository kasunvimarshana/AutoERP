<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CalculatePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tenantId = $this->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        $organizationUnitId = $this->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id')
        );
        $currentUserId = $this->attributes->get((string) config('core.current_user.id_attribute', 'current_user_id'));

        $this->merge([
            'tenant_id' => $this->input('tenant_id', is_int($tenantId) ? $tenantId : null),
            'organization_unit_id' => $this->input(
                'organization_unit_id',
                is_int($organizationUnitId) ? $organizationUnitId : null,
            ),
            'actor_id' => $this->input('actor_id', is_int($currentUserId) ? $currentUserId : null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.linked_quantity' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_type' => ['nullable', 'string', 'max:50'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
        ];
    }
}
