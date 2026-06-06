<?php

declare(strict_types=1);

namespace Modules\Core\Entities;

use InvalidArgumentException;
use Stringable;

abstract class Entity
{
    public function __construct(private readonly Stringable|string $id)
    {
        if (! $id instanceof Stringable && trim($id) === '') {
            throw new InvalidArgumentException('Entity identifier cannot be empty.');
        }
    }

    public function id(): Stringable|string
    {
        return $this->id;
    }

    public function sameIdentityAs(self $other): bool
    {
        return (string) $this->id === (string) $other->id;
    }
}
