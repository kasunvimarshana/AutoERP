<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class VoidCreditMemoService extends BaseService implements VoidCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $id = (int) ($data['id'] ?? 0);

        $creditMemo = $this->creditMemoRepository->find($id);
        if (! $creditMemo) {
            throw new CreditMemoNotFoundException($id);
        }

        if ($creditMemo->getStatus() === 'voided') {
            throw new DomainException('Credit memo is already voided.');
        }

        $creditMemo->void();

        return $this->creditMemoRepository->save($creditMemo);
    }
}
