<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class IssueCreditMemoService extends BaseService implements IssueCreditMemoServiceInterface
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

        if ($creditMemo->getStatus() !== 'draft') {
            throw new DomainException('Only draft credit memos can be issued.');
        }

        $creditMemo->issue();

        return $this->creditMemoRepository->save($creditMemo);
    }
}
