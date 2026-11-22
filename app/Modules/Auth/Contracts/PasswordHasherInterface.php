<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Auth\Contracts;

interface PasswordHasherInterface
{
    public function hash(string $value): string;

    public function verify(string $value, string $hashedValue): bool;

    public function needsRehash(string $hashedValue): bool;
}
