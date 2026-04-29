<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SearchProductCatalogRequest extends FormRequest
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
            'q' => 'nullable|string|max:255',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('product_brands', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'stock_status' => 'nullable|string|in:all,in_stock,out_of_stock',
            'pricing_type' => 'nullable|string|in:purchase,sales',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'quantity' => 'nullable|numeric|gt:0',
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'include_inactive' => 'nullable|boolean',
            'variant_attribute' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:name,sku,-name,-sku',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $headerTenantId = (int) $this->header('X-Tenant-ID');
            $payloadTenantId = (int) $this->input('tenant_id');

            if ($headerTenantId > 0 && $payloadTenantId > 0 && $headerTenantId !== $payloadTenantId) {
                $validator->errors()->add('tenant_id', 'Tenant mismatch between X-Tenant-ID header and payload.');
            }
        });
    }
}
