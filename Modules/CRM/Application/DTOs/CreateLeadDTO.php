<?php

declare(strict_types=1);

namespace Modules\CRM\Application\DTOs;

/**
 * Data Transfer Object for creating a CrmLead.
 */
final class CreateLeadDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $company,
        public readonly ?string $source,
        public readonly ?int $assignedTo,
        public readonly ?int $campaignId,
        public readonly ?string $notes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            company: isset($data['company']) ? (string) $data['company'] : null,
            source: isset($data['source']) ? (string) $data['source'] : null,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            campaignId: isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
