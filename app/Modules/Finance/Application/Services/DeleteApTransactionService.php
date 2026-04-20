<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteApTransactionServiceInterface;
use Modules\Finance\Domain\Exceptions\ApTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;

class DeleteApTransactionService extends BaseService implements DeleteApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $apTransactionRepository)
    {
        parent::__construct($apTransactionRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->apTransactionRepository->find($id)) {
            throw new ApTransactionNotFoundException($id);
        }

        return $this->apTransactionRepository->delete($id);
    }
}
