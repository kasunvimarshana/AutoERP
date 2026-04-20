<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_primary' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
