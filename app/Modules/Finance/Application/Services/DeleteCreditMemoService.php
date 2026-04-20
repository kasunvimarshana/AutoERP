<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Finance\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class DeleteCreditMemoService extends BaseService implements DeleteCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->creditMemoRepository->find($id)) {
            throw new CreditMemoNotFoundException($id);
        }

        return $this->creditMemoRepository->delete($id);
    }
}
