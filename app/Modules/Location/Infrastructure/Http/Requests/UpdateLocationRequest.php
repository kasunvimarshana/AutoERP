<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'type'        => 'sometimes|required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'timezone'    => 'nullable|string|max:100',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:locations,id',
        ];
    }
}
