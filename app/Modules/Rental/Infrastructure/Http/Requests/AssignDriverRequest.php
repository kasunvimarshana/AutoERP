<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'min:1'],
            'org_unit_id' => ['sometimes', 'integer', 'min:1'],
            'assigned_from' => ['sometimes', 'date'],
            'assigned_to' => ['sometimes', 'date', 'after_or_equal:assigned_from'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
