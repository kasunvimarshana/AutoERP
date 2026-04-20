<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindArTransactionServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;

class FindArTransactionService extends BaseService implements FindArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $arTransactionRepository)
    {
        parent::__construct($arTransactionRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
