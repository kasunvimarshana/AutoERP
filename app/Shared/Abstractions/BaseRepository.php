<?php

declare(strict_types=1);

namespace App\Shared\Abstractions;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    abstract protected function model(): string;

    protected function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        /** @var Model $model */
        $model = app($this->model());

        return $model->newQuery();
    }
}
