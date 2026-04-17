<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertUserDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_token' => 'required|string|max:512',
            'platform' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'last_active_at' => 'nullable|date',
        ];
    }
}
