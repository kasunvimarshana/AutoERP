<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id'    => ['required', 'integer', 'min:1'],
            'product_id'   => ['nullable', 'integer', 'min:1'],
            'variant_id'   => ['nullable', 'integer', 'min:1'],
            'status'       => ['nullable', 'string', 'in:active,quarantine,expired,depleted'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'lot_number'   => ['nullable', 'string', 'max:255'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:200'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'sort'         => ['nullable', 'string'],
        ];
    }
}
