<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiometricDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
            'device_type' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'location' => 'nullable|string',
            'org_unit_id' => 'nullable|integer',
            'status' => 'nullable|string|in:active,inactive,maintenance,offline',
            'metadata' => 'nullable|array',
        ];
    }
}
