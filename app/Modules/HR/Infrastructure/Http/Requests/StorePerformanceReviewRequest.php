<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

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
            'tenant_id'           => 'required|integer',
            'employee_id'         => 'required|integer',
            'reviewer_id'         => 'required|integer',
            'review_period_start' => 'required|date_format:Y-m-d',
            'review_period_end'   => 'required|date_format:Y-m-d',
            'rating'              => 'required|numeric|min:1|max:5',
            'comments'            => 'nullable|string',
            'goals'               => 'nullable|string',
            'achievements'        => 'nullable|string',
            'status'              => 'nullable|string|in:draft,submitted,acknowledged',
            'metadata'            => 'nullable|array',
        ];
    }
}
