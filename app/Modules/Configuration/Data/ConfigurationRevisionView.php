<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Data;

use DateTimeImmutable;

final readonly class ConfigurationRevisionView
{
    public function __construct(
        public int $id,
        public string $operation,
        public string $scope,
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $key,
        public int $definitionVersion,
        public bool $definitionCompatible,
        public mixed $value,
        public bool $configured,
        public bool $sensitive,
        public ?int $resultingRowVersion,
        public ?int $sourceRevisionId,
        public string $actorType,
        public ?int $actorId,
        public ?string $actorName,
        public ?string $actorEmail,
        public ?string $reason,
        public DateTimeImmutable $createdAt,
    ) {}
}
