<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\ReconcileArTransactionServiceInterface;
use Modules\Finance\Domain\Entities\ArTransaction;
use Modules\Finance\Domain\Exceptions\ArTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;

class ReconcileArTransactionService extends BaseService implements ReconcileArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $arTransactionRepository)
    {
        parent::__construct($arTransactionRepository);
    }

    protected function handle(array $data): ArTransaction
    {
        $id = (int) ($data['id'] ?? 0);

        $arTransaction = $this->arTransactionRepository->find($id);
        if (! $arTransaction) {
            throw new ArTransactionNotFoundException($id);
        }

        $arTransaction->reconcile();

        return $this->arTransactionRepository->save($arTransaction);
    }
}
