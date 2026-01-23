<?php

declare(strict_types=1);

namespace App\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Active Scope
 * 
 * Automatically filters records to only show active ones
 */
class ActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('status', 'active');
    }
}
