<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindBankTransactionServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class FindBankTransactionService extends BaseService implements FindBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $bankTransactionRepository)
    {
        parent::__construct($bankTransactionRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
