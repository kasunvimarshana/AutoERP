<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordTransactionTaxesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'tax_lines' => 'nullable|array|min:1',
            'tax_lines.*.tax_rate_id' => [
                'required_with:tax_lines',
                'integer',
                Rule::exists('tax_rates', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'tax_lines.*.taxable_amount' => 'required_with:tax_lines|numeric|min:0',
            'tax_lines.*.tax_amount' => 'required_with:tax_lines|numeric|min:0',
            'tax_lines.*.tax_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'default_tax_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'taxable_amount' => 'required_without:tax_lines|numeric|min:0',
            'tax_group_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_groups', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'product_category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'party_type' => 'nullable|in:customer,supplier',
            'region' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ];
    }
}
