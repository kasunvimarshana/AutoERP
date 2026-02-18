<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'reviewer_id' => ['required', 'integer', 'exists:employees,id'],
            'review_period_start' => ['required', 'date'],
            'review_period_end' => ['required', 'date', 'after:review_period_start'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'goals' => ['nullable', 'string'],
            'achievements' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,submitted,completed'],
        ];
    }
}
