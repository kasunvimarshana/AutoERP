<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Specifications;

final class OrSpecification implements SpecificationInterface
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {
    }

    public function isSatisfiedBy(object $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate) || $this->right->isSatisfiedBy($candidate);
    }
}
