<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeletePaymentServiceInterface;
use Modules\Finance\Domain\Exceptions\PaymentNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;

class DeletePaymentService extends BaseService implements DeletePaymentServiceInterface
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository)
    {
        parent::__construct($paymentRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $payment = $this->paymentRepository->find($id);
        if (! $payment) {
            throw new PaymentNotFoundException($id);
        }

        return $this->paymentRepository->delete($id);
    }
}
