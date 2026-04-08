<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

/**
 * Contact domain entity.
 */
class Contact
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $contactableType,
        public readonly string $contactableId,
        public readonly string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly bool $isPrimary,
    ) {}
}
