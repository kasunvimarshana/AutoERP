<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\UOM\Constants\UomType;

final class UpsertUnitOfMeasureRequest extends FormRequest
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
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'code' => array_merge($required, ['string', 'max:50']),
            'name' => array_merge($required, ['string', 'max:255']),
            'symbol' => array_merge($required, ['string', 'max:255']),
            'category' => ['nullable', 'string', 'in:'.implode(',', UomType::all())],
            'type' => ['nullable', 'string', 'in:'.implode(',', UomType::all())],
            'decimal_precision' => ['nullable', 'integer', 'min:0', 'max:8'],
            'allow_fractional_quantity' => ['nullable', 'boolean'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
