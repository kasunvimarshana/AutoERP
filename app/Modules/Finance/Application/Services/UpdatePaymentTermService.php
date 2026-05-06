<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdatePaymentTermServiceInterface;
use Modules\Finance\Application\DTOs\PaymentTermData;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\Finance\Domain\Exceptions\PaymentTermNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;

class UpdatePaymentTermService extends BaseService implements UpdatePaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $paymentTermRepository)
    {
        parent::__construct($paymentTermRepository);
    }

    protected function handle(array $data): PaymentTerm
    {
        $dto = PaymentTermData::fromArray($data);

        /** @var PaymentTerm|null $paymentTerm */
        $paymentTerm = $this->paymentTermRepository->find((int) $dto->id);
        if (! $paymentTerm) {
            throw new PaymentTermNotFoundException((int) $dto->id);
        }

        $paymentTerm->update(
            $dto->name,
            $dto->days,
            $dto->is_default,
            $dto->is_active,
            $dto->description,
            $dto->discount_days,
            $dto->discount_rate,
        );

        return $this->paymentTermRepository->save($paymentTerm);
    }
}
