<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Aggregates;

use Modules\Core\Domain\Exceptions\DomainException;

final class ResourceAggregate
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private array $attributes,
    ) {}

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function validateNotEmpty(string $context = 'Resource'): void
    {
        if ($this->attributes === []) {
            throw new DomainException(sprintf('%s payload cannot be empty.', $context));
        }
    }
}
