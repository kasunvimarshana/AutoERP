<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\Events\PaymentCompleted;
use Modules\Accounting\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class CreatePaymentService implements CreatePaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $repo) {}

    public function execute(array $data): Payment
    {
        $payment = $this->repo->create($data);

        if ($payment->getStatus() === 'completed') {
            event(new PaymentCompleted($payment->getTenantId(), $payment->getId()));
        }

        return $payment;
    }
}
