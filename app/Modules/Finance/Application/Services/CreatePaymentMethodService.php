<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentMethodServiceInterface;
use Modules\Finance\Application\DTOs\PaymentMethodData;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;

class CreatePaymentMethodService extends BaseService implements CreatePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $paymentMethodRepository)
    {
        parent::__construct($paymentMethodRepository);
    }

    protected function handle(array $data): PaymentMethod
    {
        $dto = PaymentMethodData::fromArray($data);

        $paymentMethod = new PaymentMethod(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            type: $dto->type,
            accountId: $dto->account_id,
            isActive: $dto->is_active,
        );

        return $this->paymentMethodRepository->save($paymentMethod);
    }
}
