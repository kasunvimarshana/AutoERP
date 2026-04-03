<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnAuthorizationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer',
            'rma_number'  => 'required|string|max:100',
            'return_type' => 'required|string|in:purchase_return,sales_return',
            'party_id'    => 'required|integer',
            'party_type'  => 'required|string|in:supplier,customer',
            'reason'      => 'nullable|string|max:255',
            'expires_at'  => 'nullable|string',
            'notes'       => 'nullable|string',
            'metadata'    => 'nullable|array',
            'status'      => 'string|in:pending,approved,expired,cancelled',
        ];
    }
}
