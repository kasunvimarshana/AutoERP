<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['sometimes', 'required', 'integer', 'exists:employees,id'],
            'reviewer_id' => ['sometimes', 'required', 'integer', 'exists:employees,id'],
            'review_period_start' => ['sometimes', 'required', 'date'],
            'review_period_end' => ['sometimes', 'required', 'date', 'after:review_period_start'],
            'overall_rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'goals' => ['nullable', 'string'],
            'achievements' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,submitted,completed'],
        ];
    }
}
