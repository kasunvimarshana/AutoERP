<?php

namespace Modules\IAM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permission.create');
    }

    public function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
