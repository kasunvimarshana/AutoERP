<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\DTOs;

use Modules\Configuration\Domain\Enums\ConfigurationRecordType;

final readonly class UpsertConfigurationRecordDTO
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public ConfigurationRecordType $type,
        public array $payload,
        public ?int $id = null,
        public ?int $expectedRowVersion = null,
    ) {
    }
}
