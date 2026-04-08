<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|required|string|max:255',
            'code'          => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'grade'         => 'nullable|string|max:50',
            'department_id' => 'nullable|integer',
            'metadata'      => 'nullable|array',
            'is_active'     => 'boolean',
        ];
    }
}
