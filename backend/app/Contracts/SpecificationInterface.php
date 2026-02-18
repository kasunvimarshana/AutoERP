<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Specification Interface
 * 
 * Defines a specification pattern for complex queries
 */
interface SpecificationInterface
{
    /**
     * Apply the specification to a query builder
     */
    public function apply(Builder $query): Builder;
}
