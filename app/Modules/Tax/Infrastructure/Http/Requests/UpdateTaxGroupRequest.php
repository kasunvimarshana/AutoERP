<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $taxGroupId = (int) $this->route('taxGroup');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_groups', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($taxGroupId),
            ],
            'description' => 'nullable|string',
            'row_version' => 'required|integer|min:1',
        ];
    }
}
