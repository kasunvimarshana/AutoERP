<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
            'org_unit_id' => 'nullable|integer|min:1',
            'customer_code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:individual,company',
            'status' => 'nullable|in:active,inactive,blocked',
            'currency_id' => 'nullable|integer|min:1',
            'ar_account_id' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
            'include' => 'nullable|string|max:255',
        ];
    }
}
