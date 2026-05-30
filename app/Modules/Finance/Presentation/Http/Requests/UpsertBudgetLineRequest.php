<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'row_version' => ['nullable', 'integer', 'min:1'],
            'budget_id' => ['required', 'integer', 'min:1', 'exists:budgets,id'],
            'account_id' => ['required', 'integer', 'min:1', 'exists:accounts,id'],
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:cost_centers,id'],
            'period_1_amount' => ['nullable', 'numeric'],
            'period_2_amount' => ['nullable', 'numeric'],
            'period_3_amount' => ['nullable', 'numeric'],
            'period_4_amount' => ['nullable', 'numeric'],
            'period_5_amount' => ['nullable', 'numeric'],
            'period_6_amount' => ['nullable', 'numeric'],
            'period_7_amount' => ['nullable', 'numeric'],
            'period_8_amount' => ['nullable', 'numeric'],
            'period_9_amount' => ['nullable', 'numeric'],
            'period_10_amount' => ['nullable', 'numeric'],
            'period_11_amount' => ['nullable', 'numeric'],
            'period_12_amount' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'used_amount' => ['nullable', 'numeric', 'gte:0'],
            'variance_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
