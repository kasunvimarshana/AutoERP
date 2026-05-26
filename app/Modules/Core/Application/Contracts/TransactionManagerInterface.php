<?php

declare(strict_types=1);

namespace Modules\Core\Application\Contracts;

interface TransactionManagerInterface
{
    public function runInTransaction(callable $callback): mixed;
}
