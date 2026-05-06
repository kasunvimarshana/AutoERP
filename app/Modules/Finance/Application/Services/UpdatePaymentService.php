<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
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
        if ($dto->rowVersion !== $payment->getRowVersion()) {
            throw new ConcurrentModificationException('Payment', (int) $dto->id);
        }

        $payment->update(
            paymentMethodId: $dto->paymentMethodId,
            accountId: $dto->accountId,
            amount: $dto->amount,
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            paymentDate: new \DateTimeImmutable($dto->paymentDate),
            reference: $dto->reference,
            notes: $dto->notes,
        );

        return $this->paymentRepository->save($payment);
    }
}
