<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Financial\Application\Contracts\BankAccountServiceInterface;
use Modules\Financial\Domain\Contracts\Repositories\BankAccountRepositoryInterface;

class BankAccountService extends BaseService implements BankAccountServiceInterface
{
    public function __construct(BankAccountRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — creates a bank account.
     */
    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
