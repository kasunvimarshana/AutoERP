<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Finance\Application\DTOs\CreditMemoData;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class CreateCreditMemoService extends BaseService implements CreateCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): CreditMemo
    {
        $dto = CreditMemoData::fromArray($data);

        $cm = new CreditMemo(
            tenantId: $dto->tenantId,
            partyId: $dto->partyId,
            partyType: $dto->partyType,
            creditMemoNumber: $dto->creditMemoNumber,
            amount: $dto->amount,
            issuedDate: new \DateTimeImmutable($dto->issuedDate),
            status: $dto->status,
            returnOrderId: $dto->returnOrderId,
            returnOrderType: $dto->returnOrderType,
            appliedToInvoiceId: $dto->appliedToInvoiceId,
            appliedToInvoiceType: $dto->appliedToInvoiceType,
            notes: $dto->notes,
            journalEntryId: $dto->journalEntryId,
        );

        return $this->creditMemoRepository->save($cm);
    }
}
