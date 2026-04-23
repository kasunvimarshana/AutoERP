<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class PerformanceReviewData
{
    /**
     * @param  array<int, mixed>  $goals
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly int $cycleId,
        public readonly int $reviewerId,
        public readonly array $goals = [],
        public readonly string $strengths = '',
        public readonly string $improvements = '',
        public readonly string $reviewerComments = '',
        public readonly string $employeeComments = '',
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            cycleId: (int) $data['cycle_id'],
            reviewerId: (int) $data['reviewer_id'],
            goals: isset($data['goals']) && is_array($data['goals']) ? $data['goals'] : [],
            strengths: isset($data['strengths']) ? (string) $data['strengths'] : '',
            improvements: isset($data['improvements']) ? (string) $data['improvements'] : '',
            reviewerComments: isset($data['reviewer_comments']) ? (string) $data['reviewer_comments'] : '',
            employeeComments: isset($data['employee_comments']) ? (string) $data['employee_comments'] : '',
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'cycle_id' => $this->cycleId,
            'reviewer_id' => $this->reviewerId,
            'goals' => $this->goals,
            'strengths' => $this->strengths,
            'improvements' => $this->improvements,
            'reviewer_comments' => $this->reviewerComments,
            'employee_comments' => $this->employeeComments,
            'metadata' => $this->metadata,
        ];
    }
}
