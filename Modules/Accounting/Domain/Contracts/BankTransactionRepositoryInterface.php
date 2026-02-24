<?php

namespace Modules\Accounting\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface BankTransactionRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function findUnreconciledByAccount(string $bankAccountId): \Illuminate\Support\Collection;
}
