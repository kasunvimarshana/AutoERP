<?php

declare(strict_types=1);

namespace Modules\User\Domain\Contracts;

interface UserDomainServiceInterface
{
    public function normalizeStatus(?string $status): string;

    public function normalizeEmail(string $email): string;

    public function normalizeRequiredString(string $value, string $field, int $maxLength = 255): string;

    public function normalizeNullableString(?string $value): ?string;

    /**
     * @param mixed $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(mixed $metadata): ?array;

    public function normalizeBoolean(mixed $value, bool $default = false): bool;
}
