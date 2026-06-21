<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use InvalidArgumentException;
use Stringable;

abstract class Entity
{
    private readonly string $id;

    public function __construct(Stringable|string $id)
    {
        $normalizedId = trim((string) $id);
        if ($normalizedId === '') {
            throw new InvalidArgumentException('Entity identifier cannot be empty.');
        }

        $this->id = $normalizedId;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sameIdentityAs(self $other): bool
    {
        return $other::class === static::class && $this->id === $other->id;
    }
}
