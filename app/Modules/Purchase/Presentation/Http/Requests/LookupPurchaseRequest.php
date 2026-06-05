<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class LookupPurchaseRequest extends FormRequest
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
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:180'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'purchase_order_id' => ['sometimes', 'nullable', 'integer', Rule::exists('purchase_orders', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'grn_id' => ['sometimes', 'nullable', 'integer', Rule::exists('grn_headers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
        ];
    }
}
