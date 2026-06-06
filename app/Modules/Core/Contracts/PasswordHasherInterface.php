<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface PasswordHasherInterface
{
    public function hash(string $value): string;

    public function verify(string $value, string $hashedValue): bool;

    public function needsRehash(string $hashedValue): bool;
}
