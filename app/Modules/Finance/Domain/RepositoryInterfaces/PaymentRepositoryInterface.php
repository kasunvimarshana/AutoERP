<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\Payment;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function save(Payment $payment): Payment;

    public function findByTenantAndNumber(int $tenantId, string $paymentNumber): ?Payment;

    public function findByTenantAndIdempotencyKey(int $tenantId, string $idempotencyKey): ?Payment;
}
