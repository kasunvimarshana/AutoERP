<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('organizations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:50'],
            'locale' => ['sometimes', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'max:60', 'timezone'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'address' => ['sometimes', 'array'],
            'settings' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
        ];
    }
}
