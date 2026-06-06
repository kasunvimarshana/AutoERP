<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Contracts;

interface AuthDomainServiceInterface
{
    public function normalizeNullableInt(mixed $value): ?int;

    public function normalizeRequiredString(string $value, string $field): string;

    public function normalizeNullableString(mixed $value): ?string;

    public function normalizeStatus(?string $status, string $default = 'active'): string;

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(mixed $value): ?array;
}
