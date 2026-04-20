<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindBankAccountServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;

class FindBankAccountService extends BaseService implements FindBankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $bankAccountRepository)
    {
        parent::__construct($bankAccountRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
