<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertGdnHeaderRequest extends FormRequest
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
            'tenant_id' => [...$required, 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
            'reference' => ['nullable', 'string', 'max:255'],
            'customer_id' => [...$required, 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'sales_order_id' => ['nullable', 'integer', 'min:1'],
            'gdn_number' => [...$required, 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
            'invoice_status' => ['sometimes', 'string', 'max:255'],
            'picking_status' => ['sometimes', 'string', 'max:255'],
            'delivery_status' => ['sometimes', 'string', 'max:255'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'delivered_date' => [...$required, 'date'],
            'price_list_id' => ['nullable', 'integer', 'min:1'],
            'header_discount_type' => ['nullable', 'string', 'max:255'],
            'header_discount_value' => ['nullable', 'numeric', 'min:0'],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1'],
            'tax_account_id' => ['nullable', 'integer', 'min:1'],
            'discount_account_id' => ['nullable', 'integer', 'min:1'],
            'sales_account_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
