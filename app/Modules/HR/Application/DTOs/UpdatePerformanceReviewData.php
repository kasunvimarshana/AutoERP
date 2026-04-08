<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial performance review updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload.
 */
class UpdatePerformanceReviewData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $tenant_id = null;

    public ?int $employee_id = null;

    public ?int $reviewer_id = null;

    public ?string $review_period_start = null;

    public ?string $review_period_end = null;

    public ?float $rating = null;

    public ?string $comments = null;

    public ?string $goals = null;

    public ?string $achievements = null;

    public ?string $status = null;

    public ?array $metadata = null;

    public function fill(array $data): static
    {
        $known = [
            'id', 'tenant_id', 'employee_id', 'reviewer_id', 'review_period_start',
            'review_period_end', 'rating', 'comments', 'goals', 'achievements',
            'status', 'metadata',
        ];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
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
