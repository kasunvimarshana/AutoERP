<?php

declare(strict_types=1);

namespace Modules\JobCard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\JobCard\Enums\JobTaskStatus;

/**
 * Store JobTask Request
 *
 * Validates data for creating a new job task
 */
class StoreJobTaskRequest extends FormRequest
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
            'task_description' => ['required', 'string'],
            'status' => ['sometimes', Rule::in(JobTaskStatus::values())],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_time' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'actual_time' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
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
            'task_description' => 'task description',
            'assigned_to' => 'assigned to',
            'estimated_time' => 'estimated time',
            'actual_time' => 'actual time',
        ];
    }
}
