<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Entities;

use Stringable;

abstract class Entity
{
    public function __construct(private readonly Stringable|string $id)
    {
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
