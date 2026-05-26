<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Contracts\TransactionManagerInterface;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function runInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
