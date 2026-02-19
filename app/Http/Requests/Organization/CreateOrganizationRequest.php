<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('organizations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'type' => ['sometimes', 'string', 'max:50'],
            'locale' => ['sometimes', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'max:60', 'timezone'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'address' => ['sometimes', 'array'],
            'settings' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
