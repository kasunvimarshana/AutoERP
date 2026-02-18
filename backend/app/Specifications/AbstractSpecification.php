<?php

declare(strict_types=1);

namespace App\Specifications;

use App\Contracts\SpecificationInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract Specification
 * 
 * Base class for all specifications
 */
abstract class AbstractSpecification implements SpecificationInterface
{
    /**
     * AND specification
     */
    public function and(SpecificationInterface $specification): CompositeSpecification
    {
        return new CompositeSpecification($this, $specification, 'and');
    }

    /**
     * OR specification
     */
    public function or(SpecificationInterface $specification): CompositeSpecification
    {
        return new CompositeSpecification($this, $specification, 'or');
    }

    /**
     * NOT specification
     */
    public function not(): NotSpecification
    {
        return new NotSpecification($this);
    }

    abstract public function apply(Builder $query): Builder;
}
