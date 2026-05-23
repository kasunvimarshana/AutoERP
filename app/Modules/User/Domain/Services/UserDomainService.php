<?php

declare(strict_types=1);

namespace Modules\User\Domain\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;

class UserDomainService
{
    /** @var array<int, string> */
    private const USER_STATUSES = ['active', 'inactive', 'suspended'];

    public function normalizeText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    public function normalizeEmail(string $email): string
    {
        $email = Str::lower(trim($email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email is required.');
        }

        return $email;
    }

    public function normalizeGuardName(?string $guardName): string
    {
        $guardName = $this->normalizeText($guardName) ?? (string) config('auth.defaults.guard', 'api');

        return $guardName === '' ? 'api' : $guardName;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function assertUserStatus(string $status): void
    {
        if (! in_array($status, self::USER_STATUSES, true)) {
            throw new InvalidArgumentException('User status must be active, inactive, or suspended.');
        }
    }
}
