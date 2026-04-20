<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteArTransactionServiceInterface;
use Modules\Finance\Domain\Exceptions\ArTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ArTransactionRepositoryInterface;

class DeleteArTransactionService extends BaseService implements DeleteArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $arTransactionRepository)
    {
        parent::__construct($arTransactionRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->arTransactionRepository->find($id)) {
            throw new ArTransactionNotFoundException($id);
        }

        return $this->arTransactionRepository->delete($id);
    }
}
