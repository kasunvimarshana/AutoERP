<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertUomRequest extends FormRequest
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
        $uomId = $this->route('uom');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];

        return [
            'organization_unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'uom_code' => [
                ...$required,
                'string',
                'max:50',
                Rule::unique('unit_of_measures', 'uom_code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($uomId),
            ],
            'name' => [...$required, 'string', 'max:180'],
            'symbol' => ['sometimes', 'nullable', 'string', 'max:50'],
            'decimal_precision' => ['sometimes', 'integer', 'min:0', 'max:8'],
            'is_base' => ['sometimes', 'boolean'],
            'status' => [...$required, Rule::in(['active', 'inactive'])],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
