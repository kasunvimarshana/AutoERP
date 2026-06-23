<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertWarehouseRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->tenantExists('organization_units', 'id'),
            ],
            'row_version' => [$this->isMethod('post') ? 'nullable' : 'required', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'name' => array_merge($required, [
                'string',
                'max:255',
            ]),
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', Rule::in(['standard', 'virtual', 'transit', 'quarantine'])],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_default') && $this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_default', 'Default warehouse must be active.');
            }
        });
    }
}
