<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id'       => ['required', 'integer', 'min:1'],
            'product_id'      => ['required', 'integer', 'min:1'],
            'variant_id'      => ['nullable', 'integer', 'min:1'],
            'batch_number'    => ['required', 'string', 'max:255'],
            'lot_number'      => ['nullable', 'string', 'max:255'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date'     => ['nullable', 'date'],
            'received_date'   => ['nullable', 'date'],
            'supplier_id'     => ['nullable', 'integer', 'min:1'],
            'status'          => ['nullable', 'string', 'in:active,quarantine,expired,depleted'],
            'notes'           => ['nullable', 'string'],
            'metadata'        => ['nullable', 'array'],
            'sales_price'     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
