<?php
namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'slug'   => ['sometimes', 'string', 'max:100'],
            'email'  => ['sometimes', 'email'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended,trial'],
            'plan'   => ['nullable', 'string', 'max:50'],
        ];
    }
}
