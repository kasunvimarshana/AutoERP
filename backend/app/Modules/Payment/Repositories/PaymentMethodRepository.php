<?php

declare(strict_types=1);

namespace App\Modules\Payment\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Payment\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    /**
     * PaymentMethodRepository constructor.
     *
     * @param PaymentMethod $model
     */
    public function __construct(PaymentMethod $model)
    {
        parent::__construct($model);
    }

    /**
     * Find payment method by code
     *
     * @param string $code
     * @return PaymentMethod|null
     */
    public function findByCode(string $code): ?PaymentMethod
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get active payment methods
     *
     * @return Collection
     */
    public function getActivePaymentMethods(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get payment methods by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }
}
