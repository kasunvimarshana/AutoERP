<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'sometimes|nullable|array',
            'group' => 'sometimes|required|string|max:255',
            'is_public' => 'sometimes|required|boolean',
        ];
    }
}
