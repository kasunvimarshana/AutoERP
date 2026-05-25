<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Contracts;

interface SystemUserDomainServiceInterface
{
    public function normalizeStatus(?string $value): string;

    public function normalizeOptionalText(?string $value): ?string;

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array;
}
