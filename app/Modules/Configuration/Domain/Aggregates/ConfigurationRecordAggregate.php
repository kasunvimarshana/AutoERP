<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Aggregates;

use Modules\Configuration\Application\DTOs\UpsertConfigurationRecordDTO;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;
use Modules\Configuration\Domain\Exceptions\ConfigurationConflictException;

final readonly class ConfigurationRecordAggregate
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private ConfigurationRecordType $type,
        private array $payload,
        private ?int $id,
        private ?int $expectedRowVersion,
    ) {
    }

    public static function fromDTO(UpsertConfigurationRecordDTO $dto): self
    {
        $payload = self::normalizePayload($dto->type, $dto->payload);

        return new self($dto->type, $payload, $dto->id, $dto->expectedRowVersion);
    }

    public function type(): ConfigurationRecordType
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function expectedRowVersion(): ?int
    {
        return $this->expectedRowVersion;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function normalizePayload(ConfigurationRecordType $type, array $payload): array
    {
        if (array_key_exists('code', $payload) && is_string($payload['code'])) {
            $payload['code'] = strtoupper(trim($payload['code']));
        }

        if (array_key_exists('name', $payload) && is_string($payload['name'])) {
            $payload['name'] = trim($payload['name']);
        }

        if ($type === ConfigurationRecordType::Timezone) {
            $offset = (string) ($payload['offset'] ?? '');
            if (!preg_match('/^[+\-](0[0-9]|1[0-4]):[0-5][0-9]$/', $offset)) {
                throw new ConfigurationConflictException('Timezone offset must be in +HH:MM or -HH:MM format.');
            }
        }

        if ($type === ConfigurationRecordType::Currency && array_key_exists('decimal_places', $payload)) {
            $decimalPlaces = (int) $payload['decimal_places'];
            if ($decimalPlaces < 0 || $decimalPlaces > 6) {
                throw new ConfigurationConflictException('Currency decimal_places must be between 0 and 6.');
            }
            $payload['decimal_places'] = $decimalPlaces;
        }

        return $payload;
    }
}
