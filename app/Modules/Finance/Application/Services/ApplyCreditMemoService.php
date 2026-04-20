<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class ApplyCreditMemoService extends BaseService implements ApplyCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $id = (int) ($data['id'] ?? 0);
        $invoiceId = (int) ($data['invoice_id'] ?? 0);
        $invoiceType = (string) ($data['invoice_type'] ?? '');

        $creditMemo = $this->creditMemoRepository->find($id);
        if (! $creditMemo) {
            throw new CreditMemoNotFoundException($id);
        }

        if ($creditMemo->getStatus() !== 'issued') {
            throw new DomainException('Only issued credit memos can be applied.');
        }

        $creditMemo->apply($invoiceId, $invoiceType);

        return $this->creditMemoRepository->save($creditMemo);
    }
}
