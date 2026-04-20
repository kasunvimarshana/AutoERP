<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindPaymentTermServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;

class FindPaymentTermService extends BaseService implements FindPaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $paymentTermRepository)
    {
        parent::__construct($paymentTermRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
