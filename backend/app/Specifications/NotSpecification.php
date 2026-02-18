<?php

declare(strict_types=1);

namespace App\Specifications;

use App\Contracts\SpecificationInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * NOT Specification
 * 
 * Negates a specification
 */
final class NotSpecification extends AbstractSpecification
{
    public function __construct(private readonly SpecificationInterface $specification)
    {
    }

    public function apply(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $q) {
            return $this->specification->apply($q);
        });
    }
}
