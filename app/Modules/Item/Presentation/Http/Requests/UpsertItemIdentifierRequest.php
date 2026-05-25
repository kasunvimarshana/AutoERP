<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertItemIdentifierRequest extends FormRequest
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
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'technology' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'max:255'],
            'value' => array_merge($required, ['string', 'max:255']),
            'gs1_company_prefix' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
