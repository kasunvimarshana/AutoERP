<?php

declare(strict_types=1);

namespace App\Http\Requests\Saga;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Start Saga Request - validates the distributed transaction initiation request.
 */
class StartSagaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'saga_type' => ['required', 'string', 'in:create_order,transfer_stock,return_order'],
            'tenant_id' => ['sometimes', 'uuid'],
            'payload' => ['required', 'array'],
            'payload.customer_id' => ['required_if:saga_type,create_order', 'string'],
            'payload.items' => ['required_if:saga_type,create_order', 'array'],
            'payload.items.*.product_id' => ['required', 'uuid'],
            'payload.items.*.warehouse_id' => ['required', 'uuid'],
            'payload.items.*.quantity' => ['required', 'integer', 'min:1'],
            'payload.total_amount' => ['required_if:saga_type,create_order', 'numeric', 'min:0'],
            'payload.payment_method' => ['required_if:saga_type,create_order', 'string'],
        ];
    }
}
