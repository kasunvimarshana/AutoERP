<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;

final class TenantReferenceValidator
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly CurrencyModel $currencies,
    ) {}

    public function assertActivePlan(?int $planId): void
    {
        if ($planId === null) {
            return;
        }

        $plan = $this->plans->findById($planId);
        if ($plan === null || ! (bool) $plan->get('is_active', false)) {
            throw new InvalidArgumentException('The selected tenant plan is not active.');
        }
    }

    public function assertActiveCurrency(?int $currencyId): void
    {
        if ($currencyId === null) {
            return;
        }

        if (! $this->currencies->newQuery()->whereKey($currencyId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('The selected currency is not active.');
        }
    }

    public function assertPeriod(?string $trialEndsAt, ?string $subscriptionEndsAt): void
    {
        if ($trialEndsAt === null || $subscriptionEndsAt === null) {
            return;
        }

        if (new DateTimeImmutable($subscriptionEndsAt) < new DateTimeImmutable($trialEndsAt)) {
            throw new InvalidArgumentException('Subscription end date cannot be earlier than trial end date.');
        }
    }

    public function assertPlanPricing(string $price, ?int $currencyId): void
    {
        $this->assertActiveCurrency($currencyId);

        if ($price !== '0.000000' && $currencyId === null) {
            throw new InvalidArgumentException('A currency is required for a paid tenant plan.');
        }
    }
}
