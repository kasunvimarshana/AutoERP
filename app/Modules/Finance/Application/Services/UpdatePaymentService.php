<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdatePaymentServiceInterface;
use Modules\Finance\Application\DTOs\PaymentData;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\Exceptions\PaymentNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class UpdatePaymentService extends BaseService implements UpdatePaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): Payment
    {
        $dto = PaymentData::fromArray($data);

        /** @var Payment|null $payment */
        $payment = $this->paymentRepository->find((int) $dto->id);
        if (! $payment) {
            throw new PaymentNotFoundException((int) $dto->id);
        }

        $payment->update(
            paymentMethodId: $dto->payment_method_id,
            accountId: $dto->account_id,
            amount: $dto->amount,
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            paymentDate: new \DateTimeImmutable($dto->payment_date),
            reference: $dto->reference,
            notes: $dto->notes,
        );

        return $this->paymentRepository->save($payment);
    }
}
