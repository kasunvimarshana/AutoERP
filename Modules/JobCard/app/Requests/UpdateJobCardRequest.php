<?php

declare(strict_types=1);

namespace Modules\JobCard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\JobCard\Enums\JobCardStatus;
use Modules\JobCard\Enums\JobPriority;

/**
 * Update JobCard Request
 *
 * Validates data for updating a job card
 */
class UpdateJobCardRequest extends FormRequest
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
        $jobCardId = $this->route('id');

        return [
            'appointment_id' => ['sometimes', 'nullable', 'integer', 'exists:appointments,id'],
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'job_number' => ['sometimes', 'string', 'max:50', Rule::unique('job_cards', 'job_number')->ignore($jobCardId)],
            'status' => ['sometimes', Rule::in(JobCardStatus::values())],
            'priority' => ['sometimes', Rule::in(JobPriority::values())],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
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
            'actual_hours' => 'actual hours',
            'customer_complaints' => 'customer complaints',
        ];
    }
}
