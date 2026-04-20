<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\PaymentTerm;

interface PaymentTermRepositoryInterface extends RepositoryInterface
{
    public function save(PaymentTerm $paymentTerm): PaymentTerm;

    public function findByTenantAndName(int $tenantId, string $name): ?PaymentTerm;
}
