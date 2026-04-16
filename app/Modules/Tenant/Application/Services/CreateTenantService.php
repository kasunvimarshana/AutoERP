<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Application\Factories\TenantConfigValueObjectFactory;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class CreateTenantService extends BaseService implements CreateTenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantConfigValueObjectFactory $valueObjectFactory
    )
    {
        parent::__construct($tenantRepository);
    }

    protected function handle(array $data): Tenant
    {
        $dto = TenantData::fromArray($data);

        $payload = $dto->toArray();
        $databaseConfig = $this->valueObjectFactory->databaseConfig($payload);
        $mailConfig = $this->valueObjectFactory->mailConfig($payload);
        $cacheConfig = $this->valueObjectFactory->cacheConfig($payload);
        $queueConfig = $this->valueObjectFactory->queueConfig($payload);
        $featureFlags = $this->valueObjectFactory->featureFlags($payload);
        $apiKeys = $this->valueObjectFactory->apiKeys($payload);

        $tenant = new Tenant(
            name: $dto->name,
            slug: $dto->slug,
            domain: $dto->domain,
            logoPath: $dto->logo_path,
            databaseConfig: $databaseConfig,
            mailConfig: $mailConfig,
            cacheConfig: $cacheConfig,
            queueConfig: $queueConfig,
            featureFlags: $featureFlags,
            apiKeys: $apiKeys,
            settings: $dto->settings,
            plan: $dto->plan,
            tenantPlanId: $dto->tenant_plan_id,
            status: $dto->status,
            trialEndsAt: $this->parseDateTime($dto->trial_ends_at),
            subscriptionEndsAt: $this->parseDateTime($dto->subscription_ends_at),
            active: $dto->active
        );

        $saved = $this->tenantRepository->save($tenant);
        $this->addEvent(new TenantCreated($saved));

        return $saved;
    }

    private function parseDateTime(?string $value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTimeImmutable($value);
    }
}
