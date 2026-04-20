<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\DTOs\TenantPlanData;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Domain\Events\TenantPlanCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;

class CreateTenantPlanService extends BaseService implements CreateTenantPlanServiceInterface
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $planRepository,
        private readonly SlugGeneratorInterface $slugGenerator,
    ) {
        parent::__construct($planRepository);
    }

    protected function handle(array $data): TenantPlan
    {
        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: isset($data['slug']) ? (string) $data['slug'] : null,
            sourceValue: isset($data['name']) ? (string) $data['name'] : null,
            fallback: 'plan',
        );

        $dto = TenantPlanData::fromArray($data);

        $plan = new TenantPlan(
            name: $dto->name,
            slug: $dto->slug,
            features: $dto->features,
            limits: $dto->limits,
            price: $dto->price,
            currencyCode: strtoupper($dto->currency_code),
            billingInterval: $dto->billing_interval,
            isActive: $dto->is_active
        );

        $saved = $this->planRepository->save($plan);
        $this->addEvent(new TenantPlanCreated($saved));

        return $saved;
    }
}
