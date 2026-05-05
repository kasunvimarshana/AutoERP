<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdatePaymentMethodServiceInterface;
use Modules\Finance\Application\DTOs\PaymentMethodData;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\Exceptions\PaymentMethodNotFoundException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;

class UpdatePaymentMethodService extends BaseService implements UpdatePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $paymentMethodRepository)
    {
        parent::__construct($paymentMethodRepository);
    }

    protected function handle(array $data): PaymentMethod
    {
        $dto = PaymentMethodData::fromArray($data);

        /** @var PaymentMethod|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->find((int) $dto->id);
        if (! $paymentMethod) {
            throw new PaymentMethodNotFoundException((int) $dto->id);
        }


        if ($dto->rowVersion !== $paymentMethod->getRowVersion()) {
            throw new ConcurrentModificationException('PaymentMethod', (int) $dto->id);
        }

        $paymentMethod->update($dto->name, $dto->type, $dto->accountId, $dto->isActive);

        return $this->paymentMethodRepository->save($paymentMethod);
    }
}
