<?php

declare(strict_types=1);

namespace App\Shared\Abstractions;

use Illuminate\Support\Facades\DB;

abstract class BaseHandler
{
    /**
     * Wrap execution in a database transaction.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
