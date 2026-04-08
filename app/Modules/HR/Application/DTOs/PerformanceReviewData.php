<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PerformanceReviewData extends BaseDto
{
    public int $tenant_id;

    public int $employee_id;

    public int $reviewer_id;

    public string $review_period_start;

    public string $review_period_end;

    public float $rating;

    public ?string $comments = null;

    public ?string $goals = null;

    public ?string $achievements = null;

    public string $status = 'draft';

    public ?array $metadata = null;

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
