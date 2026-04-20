<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\VoidPaymentServiceInterface;
use Modules\Finance\Domain\Entities\Payment;
use Modules\Finance\Domain\Exceptions\PaymentNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class VoidPaymentService extends BaseService implements VoidPaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): Payment
    {
        $id = (int) ($data['id'] ?? 0);

        $payment = $this->paymentRepository->find($id);
        if (! $payment) {
            throw new PaymentNotFoundException($id);
        }

        if ($payment->getStatus() === 'voided') {
            throw new DomainException('Payment is already voided.');
        }

        $payment->void();

        return $this->paymentRepository->save($payment);
    }
}
