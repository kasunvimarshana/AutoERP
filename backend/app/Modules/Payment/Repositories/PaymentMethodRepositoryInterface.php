<?php

declare(strict_types=1);

namespace App\Modules\Payment\Repositories;

use App\Modules\Payment\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

interface PaymentMethodRepositoryInterface
{
    /**
     * Find payment method by code
     *
     * @param string $code
     * @return PaymentMethod|null
     */
    public function findByCode(string $code): ?PaymentMethod;

    /**
     * Get active payment methods
     *
     * @return Collection
     */
    public function getActivePaymentMethods(): Collection;

    /**
     * Get payment methods by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection;
}
