<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class AssignRentalDriverRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'min:1'],
            'assignment_role' => ['nullable', Rule::in(['primary', 'relief'])],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after:assigned_from'],
            'is_primary' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
