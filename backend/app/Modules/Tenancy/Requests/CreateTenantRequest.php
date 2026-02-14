<?php

namespace App\Modules\Tenancy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|unique:tenants,domain|max:255',
            'database' => 'required|string|max:255',
            'settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive,trial,suspended',
            'trial_ends_at' => 'nullable|date',
        ];
    }
}
