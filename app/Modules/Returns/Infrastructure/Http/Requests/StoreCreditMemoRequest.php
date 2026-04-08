<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditMemoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'reference_number' => 'required|string|max:100',
            'party_id'         => 'required|integer',
            'party_type'       => 'required|string|in:supplier,customer',
            'stock_return_id'  => 'nullable|integer',
            'amount'           => 'numeric|min:0',
            'currency'         => 'string|size:3',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
            'status'           => 'string|in:draft,issued,applied,voided',
        ];
    }
}
