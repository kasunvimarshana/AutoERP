<?php

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name'      => 'required|string|max:255|unique:permissions,name,NULL,id,tenant_id,' . $tenantId,
        ];
    }
}
