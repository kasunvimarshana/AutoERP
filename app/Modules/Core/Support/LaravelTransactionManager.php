<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TransactionManagerInterface;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function runInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
