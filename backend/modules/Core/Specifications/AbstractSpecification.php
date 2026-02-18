<?php

namespace Modules\Core\Specifications;

use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract Specification Base Class
 * 
 * Provides default implementation for composing specifications
 */
abstract class AbstractSpecification implements Specification
{
    /**
     * Apply the specification to a query builder
     * Must be implemented by concrete specifications
     */
    abstract public function apply(Builder $query): Builder;

    /**
     * Combine with another specification using AND logic
     */
    public function and(Specification $specification): Specification
    {
        return new AndSpecification($this, $specification);
    }

    /**
     * Combine with another specification using OR logic
     */
    public function or(Specification $specification): Specification
    {
        return new OrSpecification($this, $specification);
    }

    /**
     * Negate this specification
     */
    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}

/**
 * AND Specification - Combines two specifications with AND logic
 */
class AndSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right
    ) {}

    public function apply(Builder $query): Builder
    {
        return $this->right->apply($this->left->apply($query));
    }
}

/**
 * OR Specification - Combines two specifications with OR logic
 */
class OrSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $this->left->apply($q);
        })->orWhere(function (Builder $q) {
            $this->right->apply($q);
        });
    }
}

/**
 * NOT Specification - Negates a specification
 */
class NotSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly Specification $specification
    ) {}

    public function apply(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $q) {
            $this->specification->apply($q);
        });
    }
}
