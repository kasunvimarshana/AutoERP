<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

abstract class ValueObject
{
    /**
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * @return array<string, scalar|null>
     */
    abstract public function toArray(): array;

    /**
     * @return array<int, scalar|null>
     */
    abstract protected function primitives(): array;

    final public function equals(self $other): bool
    {
        return $this->primitives() === $other->primitives();
    }
}
