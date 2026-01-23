<?php

declare(strict_types=1);

namespace Modules\JobCard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\JobCard\Enums\JobCardStatus;
use Modules\JobCard\Enums\JobPriority;

/**
 * Store JobCard Request
 *
 * Validates data for creating a new job card
 */
class StoreJobCardRequest extends FormRequest
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
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'job_number' => ['sometimes', 'string', 'max:50', 'unique:job_cards,job_number'],
            'status' => ['sometimes', Rule::in(JobCardStatus::values())],
            'priority' => ['sometimes', Rule::in(JobPriority::values())],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'notes' => ['nullable', 'string'],
            'customer_complaints' => ['nullable', 'string'],
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
            'appointment_id' => 'appointment',
            'vehicle_id' => 'vehicle',
            'customer_id' => 'customer',
            'branch_id' => 'branch',
            'job_number' => 'job number',
            'technician_id' => 'technician',
            'supervisor_id' => 'supervisor',
            'estimated_hours' => 'estimated hours',
            'customer_complaints' => 'customer complaints',
        ];
    }
}
