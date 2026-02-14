<?php

namespace App\Modules\Tenancy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255|unique:tenants,domain,' . $this->route('id'),
            'settings' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive,trial,suspended',
            'trial_ends_at' => 'nullable|date',
        ];
    }
}
