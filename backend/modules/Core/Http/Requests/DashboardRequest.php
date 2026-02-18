<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dashboard Request Validation
 * 
 * Validates input parameters for dashboard endpoints to prevent
 * SQL injection and ensure data integrity.
 */
class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // All authenticated users can view their tenant's dashboard
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => 'sometimes|string|in:day,week,month,year',
            'limit' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'Period must be one of: day, week, month, year',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 100',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }

    /**
     * Get validated period with default value
     */
    public function getPeriod(): string
    {
        return $this->validated()['period'] ?? 'month';
    }

    /**
     * Get validated limit with default value
     */
    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 10;
    }
}
