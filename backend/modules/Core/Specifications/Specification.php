<?php

namespace Modules\Core\Specifications;

use Illuminate\Database\Eloquent\Builder;

/**
 * Specification Pattern Interface
 * 
 * Encapsulates business rules for querying domain objects
 * Allows composable and reusable query logic
 */
interface Specification
{
    /**
     * Apply the specification to a query builder
     *
     * @param Builder $query
     * @return Builder
     */
    public function apply(Builder $query): Builder;

    /**
     * Combine with another specification using AND logic
     *
     * @param Specification $specification
     * @return Specification
     */
    public function and(Specification $specification): Specification;

    /**
     * Combine with another specification using OR logic
     *
     * @param Specification $specification
     * @return Specification
     */
    public function or(Specification $specification): Specification;

    /**
     * Negate this specification
     *
     * @return Specification
     */
    public function not(): Specification;
}
