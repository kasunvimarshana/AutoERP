<?php

declare(strict_types=1);

namespace Modules\Appointment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Appointment\Enums\BayStatus;
use Modules\Appointment\Enums\BayType;

/**
 * Update Bay Request
 *
 * Validates data for updating a bay
 */
class UpdateBayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'bay_number' => ['sometimes', 'string', 'max:50'],
            'bay_type' => ['sometimes', Rule::in(BayType::values())],
            'status' => ['sometimes', Rule::in(BayStatus::values())],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'branch',
            'bay_number' => 'bay number',
            'bay_type' => 'bay type',
        ];
    }
}
