<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Application\Contracts\UpdateCreditMemoServiceInterface;
use Modules\Finance\Application\DTOs\CreditMemoData;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\Exceptions\CreditMemoNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class UpdateCreditMemoService extends BaseService implements UpdateCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $dto = CreditMemoData::fromArray($data);
        /** @var CreditMemo|null $cm */
        $cm = $this->creditMemoRepository->find((int) $dto->id);
        if (! $cm) {
            throw new CreditMemoNotFoundException((int) $dto->id);
        }
        if ($dto->rowVersion !== $cm->getRowVersion()) {
            throw new ConcurrentModificationException('CreditMemo', (int) $dto->id);
        }
        if ($dto->status === 'issued') {
            $cm->issue();
        } elseif ($dto->status === 'voided') {
            $cm->void();
        } elseif ($dto->status === 'applied' && $dto->appliedToInvoiceId !== null && $dto->appliedToInvoiceType !== null) {
            $cm->apply($dto->appliedToInvoiceId, $dto->appliedToInvoiceType);
        }

        return $this->creditMemoRepository->save($cm);
    }
}
