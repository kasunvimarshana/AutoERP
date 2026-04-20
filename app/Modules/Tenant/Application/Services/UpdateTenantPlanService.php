<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Domain\Events\TenantPlanUpdated;
use Modules\Tenant\Domain\Exceptions\TenantPlanNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;

class UpdateTenantPlanService extends BaseService implements UpdateTenantPlanServiceInterface
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $planRepository,
        private readonly SlugGeneratorInterface $slugGenerator,
    ) {
        parent::__construct($planRepository);
    }

    protected function handle(array $data): TenantPlan
    {
        $id = (int) $data['id'];
        $existing = $this->planRepository->find($id);

        if (! $existing) {
            throw new TenantPlanNotFoundException($id);
        }

        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: array_key_exists('slug', $data) ? (string) $data['slug'] : null,
            sourceValue: array_key_exists('name', $data) ? (string) $data['name'] : $existing->getName(),
            fallback: $existing->getSlug(),
        );

        $name = array_key_exists('name', $data)
            ? (string) $data['name']
            : $existing->getName();

        $slug = array_key_exists('slug', $data)
            ? (string) $data['slug']
            : $existing->getSlug();

        $features = array_key_exists('features', $data)
            ? $data['features']
            : $existing->getFeatures();

        $limits = array_key_exists('limits', $data)
            ? $data['limits']
            : $existing->getLimits();

        $price = array_key_exists('price', $data)
            ? (string) $data['price']
            : $existing->getPrice();

        $currencyCode = array_key_exists('currency_code', $data)
            ? strtoupper((string) $data['currency_code'])
            : $existing->getCurrencyCode();

        $billingInterval = array_key_exists('billing_interval', $data)
            ? (string) $data['billing_interval']
            : $existing->getBillingInterval();

        $isActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : $existing->isActive();

        $existing->update(
            name: $name,
            slug: $slug,
            features: $features,
            limits: $limits,
            price: $price,
            currencyCode: $currencyCode,
            billingInterval: $billingInterval,
            isActive: $isActive
        );

        $saved = $this->planRepository->save($existing);
        $this->addEvent(new TenantPlanUpdated($saved));

        return $saved;
    }
}
