<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface TransactionManagerInterface
{
    public function runInTransaction(callable $callback): mixed;
}
