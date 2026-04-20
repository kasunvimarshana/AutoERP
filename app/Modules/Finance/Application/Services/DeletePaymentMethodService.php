<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeletePaymentMethodServiceInterface;
use Modules\Finance\Domain\Exceptions\PaymentMethodNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;

class DeletePaymentMethodService extends BaseService implements DeletePaymentMethodServiceInterface
{
    public function __construct(private readonly PaymentMethodRepositoryInterface $paymentMethodRepository)
    {
        parent::__construct($paymentMethodRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $paymentMethod = $this->paymentMethodRepository->find($id);
        if (! $paymentMethod) {
            throw new PaymentMethodNotFoundException($id);
        }

        return $this->paymentMethodRepository->delete($id);
    }
}
