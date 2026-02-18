<?php

declare(strict_types=1);

namespace App\Specifications;

use App\Contracts\SpecificationInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Composite Specification
 * 
 * Combines multiple specifications with AND/OR logic
 */
final class CompositeSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
        private readonly string $operator
    ) {
        if (!in_array($operator, ['and', 'or'])) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }
    }

    public function apply(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q = $this->left->apply($q);
            
            if ($this->operator === 'and') {
                $q = $this->right->apply($q);
            } else {
                $q = $q->orWhere(function (Builder $subQ) {
                    return $this->right->apply($subQ);
                });
            }
            
            return $q;
        });
    }
}
