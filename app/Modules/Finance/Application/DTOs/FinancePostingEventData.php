<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

final readonly class FinancePostingEventData
{
    /**
     * @param list<array<string, mixed>> $lines
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $eventType,
        public string $idempotencyKey,
        public string $entryNumber,
        public string $entryDate,
        public string $description,
        public ?int $fiscalPeriodId,
        public ?string $referenceType,
        public int|string|null $referenceId,
        public ?string $sourceModule,
        public ?int $actorUserId,
        public array $lines,
        public array $metadata,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $lines = [];
        foreach ((array) ($payload['lines'] ?? []) as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }

        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0,
            array_key_exists('organization_unit_id', $payload) && $payload['organization_unit_id'] !== null
                ? (int) $payload['organization_unit_id']
                : null,
            trim((string) ($payload['event_type'] ?? '')),
            trim((string) ($payload['idempotency_key'] ?? '')),
            trim((string) ($payload['entry_number'] ?? '')),
            trim((string) ($payload['entry_date'] ?? '')),
            trim((string) ($payload['description'] ?? '')),
            array_key_exists('fiscal_period_id', $payload) && $payload['fiscal_period_id'] !== null
                ? (int) $payload['fiscal_period_id']
                : null,
            isset($payload['reference_type']) ? trim((string) $payload['reference_type']) : null,
            $payload['reference_id'] ?? null,
            isset($payload['source_module']) ? trim((string) $payload['source_module']) : null,
            array_key_exists('actor_user_id', $payload) && $payload['actor_user_id'] !== null
                ? (int) $payload['actor_user_id']
                : null,
            $lines,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }
}

