<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Specifications;

final class NotSpecification implements SpecificationInterface
{
    public function __construct(private readonly SpecificationInterface $specification)
    {
    }

    public function isSatisfiedBy(object $candidate): bool
    {
        return ! $this->specification->isSatisfiedBy($candidate);
    }
}
