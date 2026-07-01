<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\UOM\Constants\UomCategory;
use Modules\UOM\Constants\UomType;

class UpsertUnitOfMeasureRequest extends TenantScopedRequest
{
    private bool $clientProvidedOrganizationUnitId = false;

    protected function prepareForValidation(): void
    {
        $this->clientProvidedOrganizationUnitId = $this->request->has('organization_unit_id')
            || $this->query->has('organization_unit_id');

        parent::prepareForValidation();

        $normalized = [];
        foreach (['type', 'category'] as $field) {
            if ($this->filled($field)) {
                $normalized[$field] = strtolower(trim((string) $this->input($field)));
            }
        }

        if ($this->filled('code')) {
            $normalized['code'] = strtoupper(trim((string) $this->input('code')));
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

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
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => [
                Rule::prohibitedIf($this->clientProvidedOrganizationUnitId),
                'nullable',
                'integer',
                'min:1',
                $this->tenantExists('organization_units', 'id'),
            ],
            'metadata' => ['nullable', 'array'],
            'code' => array_merge($required, ['string', 'max:50']),
            'name' => array_merge($required, ['string', 'max:255']),
            'symbol' => array_merge($required, ['string', 'max:255']),
            'type' => ['nullable', 'string', 'in:'.implode(',', UomType::all())],
            'category' => ['nullable', 'string', 'in:'.implode(',', UomCategory::all())],
            'decimal_precision' => ['nullable', 'integer', 'min:0', 'max:8'],
            'allow_fractional_quantity' => ['nullable', 'boolean'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
