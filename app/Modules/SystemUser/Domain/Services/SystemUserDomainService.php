<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Services;

use InvalidArgumentException;
use Modules\SystemUser\Domain\Constants\SystemUserStatus;
use Modules\SystemUser\Domain\Contracts\SystemUserDomainServiceInterface;

final class SystemUserDomainService implements SystemUserDomainServiceInterface
{
    public function normalizeStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return SystemUserStatus::ACTIVE;
        }

        if (! SystemUserStatus::isValid($normalized)) {
            throw new InvalidArgumentException('Status must be active, inactive, or blocked.');
        }

        return $normalized;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
