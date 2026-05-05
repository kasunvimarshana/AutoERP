<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubstituteDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'assigned_from' => ['sometimes', 'date'],
            'assigned_to' => ['sometimes', 'nullable', 'date'],
            'replaced_at' => ['sometimes', 'date'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
