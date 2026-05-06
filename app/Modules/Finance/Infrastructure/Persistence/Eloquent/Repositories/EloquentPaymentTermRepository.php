<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentTermModel;

class EloquentPaymentTermRepository extends EloquentRepository implements PaymentTermRepositoryInterface
{
    public function __construct(PaymentTermModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PaymentTermModel $model): PaymentTerm => $this->mapModelToDomainEntity($model));
    }

    public function save(PaymentTerm $paymentTerm): PaymentTerm
    {
        $data = [
            'tenant_id' => $paymentTerm->getTenantId(),
            'name' => $paymentTerm->getName(),
            'days' => $paymentTerm->getDays(),
            'is_default' => $paymentTerm->isDefault(),
            'is_active' => $paymentTerm->isActive(),
            'description' => $paymentTerm->getDescription(),
            'discount_days' => $paymentTerm->getDiscountDays(),
            'discount_rate' => $paymentTerm->getDiscountRate(),
            'row_version' => $paymentTerm->getRowVersion(),
        ];

        if ($paymentTerm->getId()) {
            $model = $this->update($paymentTerm->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var PaymentTermModel $model */
        return $this->toDomainEntity($model);
    }

    public function findByTenantAndName(int $tenantId, string $name): ?PaymentTerm
    {
        /** @var PaymentTermModel|null $model */
        $model = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(PaymentTermModel $model): PaymentTerm
    {
        return new PaymentTerm(
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            days: (int) $model->days,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            id: (int) $model->id,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            description: $model->description,
            discountDays: $model->discount_days,
            discountRate: $model->discount_rate !== null ? (float) $model->discount_rate : null,
        );
    }
}
