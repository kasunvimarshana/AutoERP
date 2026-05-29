<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Presentation\Http\Requests\Concerns\ResolvesPricingTenant;

final class UpsertDiscountRequest extends FormRequest
{
    use ResolvesPricingTenant;

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
            'metadata' => ['nullable', 'array'],
            'code' => ['nullable', 'string', 'max:255'],
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_value' => [$required, 'numeric', 'min:0'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0', 'gte:min_quantity'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_stackable' => ['nullable', 'boolean'],
            'is_exclusive' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
