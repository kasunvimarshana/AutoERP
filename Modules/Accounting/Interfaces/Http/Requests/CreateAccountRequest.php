<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,revenue,expense'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
