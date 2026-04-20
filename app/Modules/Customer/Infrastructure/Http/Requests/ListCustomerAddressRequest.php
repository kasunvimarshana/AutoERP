<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:billing,shipping,other',
            'is_default' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
