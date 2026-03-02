<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
