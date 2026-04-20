<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\ReconcileApTransactionServiceInterface;
use Modules\Finance\Domain\Entities\ApTransaction;
use Modules\Finance\Domain\Exceptions\ApTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;

class ReconcileApTransactionService extends BaseService implements ReconcileApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $apTransactionRepository)
    {
        parent::__construct($apTransactionRepository);
    }

    protected function handle(array $data): ApTransaction
    {
        $id = (int) ($data['id'] ?? 0);

        $apTransaction = $this->apTransactionRepository->find($id);
        if (! $apTransaction) {
            throw new ApTransactionNotFoundException($id);
        }

        $apTransaction->reconcile();

        return $this->apTransactionRepository->save($apTransaction);
    }
}
