<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeletePaymentTermServiceInterface;
use Modules\Finance\Domain\Exceptions\PaymentTermNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;

class DeletePaymentTermService extends BaseService implements DeletePaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $paymentTermRepository)
    {
        parent::__construct($paymentTermRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $paymentTerm = $this->paymentTermRepository->find($id);
        if (! $paymentTerm) {
            throw new PaymentTermNotFoundException($id);
        }

        return $this->paymentTermRepository->delete($id);
    }
}
