<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

interface PasswordHasherInterface
{
    public function hash(string $value): string;

    public function needsRehash(string $hashedValue): bool;
}
