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
            tenantId: $dto->tenant_id,
            partyId: $dto->party_id,
            partyType: $dto->party_type,
            creditMemoNumber: $dto->credit_memo_number,
            amount: $dto->amount,
            issuedDate: new \DateTimeImmutable($dto->issued_date),
            status: $dto->status,
            returnOrderId: $dto->return_order_id,
            returnOrderType: $dto->return_order_type,
            appliedToInvoiceId: $dto->applied_to_invoice_id,
            appliedToInvoiceType: $dto->applied_to_invoice_type,
            notes: $dto->notes,
            journalEntryId: $dto->journal_entry_id,
        );

        return $this->creditMemoRepository->save($cm);
    }
}
