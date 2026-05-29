<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertDiscountRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'tenant_id' => [$required, 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'discount_id' => [$required, 'integer', 'min:1'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'logical_operator' => ['nullable', 'string', 'in:and,or'],
            'condition_type' => ['nullable', 'string', 'max:60'],
            'field' => [$required, 'string', 'max:255'],
            'operator' => [$required, 'string', 'max:60'],
            'value_text' => ['nullable', 'string'],
            'value_number' => ['nullable', 'numeric'],
            'value_boolean' => ['nullable', 'boolean'],
            'value_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
