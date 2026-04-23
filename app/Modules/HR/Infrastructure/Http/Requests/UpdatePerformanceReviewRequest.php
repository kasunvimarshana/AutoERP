<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

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
            'overall_rating' => 'nullable|string|in:outstanding,exceeds_expectations,meets_expectations,needs_improvement,unsatisfactory',
            'goals' => 'nullable|array',
            'strengths' => 'nullable|string',
            'improvements' => 'nullable|string',
            'reviewer_comments' => 'nullable|string',
            'employee_comments' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
