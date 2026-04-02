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
            'review_period_start' => 'sometimes|required|date_format:Y-m-d',
            'review_period_end'   => 'sometimes|required|date_format:Y-m-d',
            'rating'              => 'sometimes|required|numeric|min:1|max:5',
            'comments'            => 'nullable|string',
            'goals'               => 'nullable|string',
            'achievements'        => 'nullable|string',
            'status'              => 'nullable|string|in:draft,submitted,acknowledged',
            'metadata'            => 'nullable|array',
        ];
    }
}
