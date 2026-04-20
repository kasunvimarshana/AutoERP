<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Contracts\SlugGeneratorInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Factories\TenantConfigValueObjectFactory;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UpdateTenantService extends BaseService implements UpdateTenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantConfigValueObjectFactory $valueObjectFactory,
        private readonly SlugGeneratorInterface $slugGenerator,
    ) {
        parent::__construct($tenantRepository);
    }

    protected function handle(array $data): Tenant
    {
        $tenantId = (int) $data['id'];

        $tenant = $this->tenantRepository->find($tenantId);
        if (! $tenant) {
            throw new TenantNotFoundException($tenantId);
        }

        $data['slug'] = $this->slugGenerator->generate(
            preferredValue: array_key_exists('slug', $data) ? (string) $data['slug'] : null,
            sourceValue: array_key_exists('name', $data) ? (string) $data['name'] : $tenant->getName(),
            fallback: $tenant->getSlug(),
        );

        $name = $data['name'] ?? $tenant->getName();
        $slug = $data['slug'] ?? $tenant->getSlug();
        $domain = array_key_exists('domain', $data) ? $data['domain'] : $tenant->getDomain();
        $logoPath = array_key_exists('logo_path', $data) ? $data['logo_path'] : $tenant->getLogoPath();
        $settings = array_key_exists('settings', $data) ? $data['settings'] : $tenant->getSettings();
        $plan = $data['plan'] ?? $tenant->getPlan();
        $tenantPlanId = array_key_exists('tenant_plan_id', $data) ? $data['tenant_plan_id'] : $tenant->getTenantPlanId();
        $status = $data['status'] ?? $tenant->getStatus();
        $trialEndsAt = $this->resolveDateTime('trial_ends_at', $data, $tenant->getTrialEndsAt());
        $subscriptionEndsAt = $this->resolveDateTime('subscription_ends_at', $data, $tenant->getSubscriptionEndsAt());

        $payload = [
            'database_config' => $data['database_config'] ?? $tenant->getDatabaseConfig()->toArray(),
            'mail_config' => array_key_exists('mail_config', $data)
                ? $data['mail_config']
                : $tenant->getMailConfig()?->toArray(),
            'cache_config' => array_key_exists('cache_config', $data)
                ? $data['cache_config']
                : $tenant->getCacheConfig()?->toArray(),
            'queue_config' => array_key_exists('queue_config', $data)
                ? $data['queue_config']
                : $tenant->getQueueConfig()?->toArray(),
            'feature_flags' => $data['feature_flags'] ?? $tenant->getFeatureFlags()->toArray(),
            'api_keys' => $data['api_keys'] ?? $tenant->getApiKeys()->toArray(),
        ];

        $databaseConfig = $this->valueObjectFactory->databaseConfig($payload);
        $mailConfig = $this->valueObjectFactory->mailConfig($payload, $tenant->getMailConfig()?->toArray());
        $cacheConfig = $this->valueObjectFactory->cacheConfig($payload, $tenant->getCacheConfig()?->toArray());
        $queueConfig = $this->valueObjectFactory->queueConfig($payload, $tenant->getQueueConfig()?->toArray());
        $featureFlags = $this->valueObjectFactory->featureFlags($payload);
        $apiKeys = $this->valueObjectFactory->apiKeys($payload);
        $active = array_key_exists('active', $data) ? (bool) $data['active'] : $tenant->isActive();

        $tenant->update(
            name: $name,
            slug: $slug,
            domain: $domain,
            logoPath: $logoPath,
            databaseConfig: $databaseConfig,
            mailConfig: $mailConfig,
            cacheConfig: $cacheConfig,
            queueConfig: $queueConfig,
            featureFlags: $featureFlags,
            apiKeys: $apiKeys,
            settings: $settings,
            plan: $plan,
            tenantPlanId: $tenantPlanId,
            status: $status,
            trialEndsAt: $trialEndsAt,
            subscriptionEndsAt: $subscriptionEndsAt,
            active: $active
        );

        $saved = $this->tenantRepository->save($tenant);
        $this->addEvent(new TenantUpdated($saved));

        return $saved;
    }

    private function resolveDateTime(string $key, array $data, ?\DateTimeInterface $current): ?\DateTimeInterface
    {
        if (! array_key_exists($key, $data)) {
            return $current;
        }

        $value = $data[$key];

        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTimeImmutable((string) $value);
    }
}
