<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Registration;

use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    private function __construct() {}
}
