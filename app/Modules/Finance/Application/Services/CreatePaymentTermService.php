<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentTermServiceInterface;
use Modules\Finance\Application\DTOs\PaymentTermData;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;

class CreatePaymentTermService extends BaseService implements CreatePaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $paymentTermRepository)
    {
        parent::__construct($paymentTermRepository);
    }

    protected function handle(array $data): PaymentTerm
    {
        $dto = PaymentTermData::fromArray($data);

        $paymentTerm = new PaymentTerm(
            tenantId: $dto->tenantId,
            name: $dto->name,
            days: $dto->days,
            isDefault: $dto->is_default,
            isActive: $dto->isActive,
        );

        return $this->paymentTermRepository->save($paymentTerm);
    }
}
