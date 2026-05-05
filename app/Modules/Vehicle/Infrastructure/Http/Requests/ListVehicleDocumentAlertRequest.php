<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListVehicleDocumentAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'expiry_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}
