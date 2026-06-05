<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertCustomerRequest extends FormRequest
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
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();
        $customerId = $this->route('customer');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];

        return [
            'organization_unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'customer_code' => [
                ...$required,
                'string',
                'max:60',
                Rule::unique('customers', 'customer_code')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore($customerId),
            ],
            'name' => [...$required, 'string', 'max:180'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:180'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0'],
            'status' => [...$required, Rule::in(['active', 'inactive'])],
            'notes' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'array'],
            'address.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.address_line_1' => ['required_with:address', 'string', 'max:255'],
            'address.address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state_province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.postal_code' => ['required_with:address', 'string', 'max:255'],
            'address.country_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
