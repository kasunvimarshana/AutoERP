<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:asset,liability,equity,income,expense',
            'subtype'     => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'currency'    => 'nullable|string|size:3',
            'balance'     => 'nullable|numeric',
            'is_system'   => 'nullable|boolean',
            'parent_id'   => 'nullable|integer|exists:accounts,id',
            'status'      => 'nullable|string|in:active,inactive',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
