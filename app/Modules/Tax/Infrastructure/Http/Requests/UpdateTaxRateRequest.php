<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $taxGroupId = (int) $this->route('taxGroup');
        $taxRateId = (int) $this->route('taxRate');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_rates', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('tax_group_id', $taxGroupId))
                    ->ignore($taxRateId),
            ],
            'rate' => 'required|numeric|min:0',
            'type' => 'nullable|in:percentage,fixed',
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'is_compound' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'row_version' => 'required|integer|min:1',
        ];
    }
}
