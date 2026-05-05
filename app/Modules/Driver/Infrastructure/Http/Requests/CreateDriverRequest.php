<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDriverRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'employee_id' => 'nullable|string|uuid',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:driver_models,email',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'driver_type' => 'nullable|string|in:employee,contractor',
            'address' => 'nullable|string',
            'id_number' => 'nullable|string|max:50',
            'hire_date' => 'nullable|date',
            'is_available' => 'nullable|boolean',
        ];
    }
}
