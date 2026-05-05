<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Application\DTOs\PaymentData;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class CreatePaymentService extends BaseService implements CreatePaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): Payment
    {
        $dto = PaymentData::fromArray($data);

        $payment = new Payment(
            tenantId: $dto->tenant_id,
            paymentNumber: $dto->payment_number,
            direction: $dto->direction,
            partyType: $dto->party_type,
            partyId: $dto->party_id,
            paymentMethodId: $dto->payment_method_id,
            accountId: $dto->account_id,
            amount: $dto->amount,
            currencyId: $dto->currency_id,
            paymentDate: new \DateTimeImmutable($dto->payment_date),
            exchangeRate: $dto->exchange_rate,
            baseAmount: $dto->base_amount,
            status: $dto->status,
            reference: $dto->reference,
            notes: $dto->notes,
            journalEntryId: $dto->journal_entry_id,
        );

        return $this->paymentRepository->save($payment);
    }
}
