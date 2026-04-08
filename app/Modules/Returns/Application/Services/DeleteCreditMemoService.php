<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Returns\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class DeleteCreditMemoService extends BaseService implements DeleteCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $memo = $this->creditMemoRepository->find($id);

        if (! $memo) {
            throw new CreditMemoNotFoundException($id);
        }

        return $this->creditMemoRepository->delete($id);
    }
}
