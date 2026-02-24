<?php
namespace Modules\Tenant\Domain\Entities;
class Tenant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $domain,
        public readonly string $status,
        public readonly string $timezone,
        public readonly string $defaultCurrency,
        public readonly string $locale,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
