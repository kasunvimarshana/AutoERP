<?php

declare(strict_types=1);

namespace Modules\Auth\Security;

use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

final class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(self::minimumLength())
            ->mixedCase()->numbers()->symbols();
    }

    public static function assert(string $password): void
    {
        if (mb_strlen($password) < self::minimumLength()
            || preg_match('/[a-z]/', $password) !== 1
            || preg_match('/[A-Z]/', $password) !== 1
            || preg_match('/[0-9]/', $password) !== 1
            || preg_match('/[^A-Za-z0-9]/', $password) !== 1
        ) {
            throw new InvalidArgumentException('Password does not satisfy the authentication password policy.');
        }
    }

    private static function minimumLength(): int
    {
        return max(12, (int) config('module-auth.password.minimum_length', 12));
    }

    private function __construct() {}
}
