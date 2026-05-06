<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentMethodModel;

class EloquentPaymentMethodRepository extends EloquentRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(PaymentMethodModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PaymentMethodModel $model): PaymentMethod => $this->mapModelToDomainEntity($model));
    }

    public function save(PaymentMethod $paymentMethod): PaymentMethod
    {
        $data = [
            'tenant_id' => $paymentMethod->getTenantId(),
            'name' => $paymentMethod->getName(),
            'type' => $paymentMethod->getType(),
            'account_id' => $paymentMethod->getAccountId(),
            'is_active' => $paymentMethod->isActive(),
        ];

        if ($paymentMethod->getId()) {
            $model = $this->update($paymentMethod->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var PaymentMethodModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapModelToDomainEntity(PaymentMethodModel $model): PaymentMethod
    {
        return new PaymentMethod(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            type: (string) $model->type,
            accountId: $model->account_id !== null ? (int) $model->account_id : null,
            isActive: (bool) $model->is_active,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
