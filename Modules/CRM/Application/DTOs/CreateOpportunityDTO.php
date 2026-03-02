<?php

declare(strict_types=1);

namespace Modules\CRM\Application\DTOs;

/**
 * Data Transfer Object for creating a CrmOpportunity.
 *
 * expectedRevenue and probability MUST be passed as numeric strings for BCMath precision.
 */
final class CreateOpportunityDTO
{
    public function __construct(
        public readonly ?int $leadId,
        public readonly int $pipelineStageId,
        public readonly string $title,
        public readonly string $expectedRevenue,
        public readonly ?string $closeDate,
        public readonly ?int $assignedTo,
        public readonly string $probability,
        public readonly ?string $notes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            leadId: isset($data['lead_id']) ? (int) $data['lead_id'] : null,
            pipelineStageId: (int) $data['pipeline_stage_id'],
            title: (string) $data['title'],
            expectedRevenue: (string) $data['expected_revenue'],
            closeDate: isset($data['close_date']) ? (string) $data['close_date'] : null,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            probability: (string) $data['probability'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
