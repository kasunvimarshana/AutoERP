<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use Modules\Tenant\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Domain\ValueObjects\CacheConfig;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;
use Modules\Tenant\Domain\ValueObjects\MailConfig;
use Modules\Tenant\Domain\ValueObjects\QueueConfig;

class Tenant
{
    private ?int $id;

    private string $name;

    private string $slug;

    private ?string $domain;

    private ?string $logoPath;

    private DatabaseConfig $databaseConfig;

    private ?MailConfig $mailConfig;

    private ?CacheConfig $cacheConfig;

    private ?QueueConfig $queueConfig;

    private FeatureFlags $featureFlags;

    private ApiKeys $apiKeys;

    /** @var array<string, mixed>|null */
    private ?array $settings;

    private string $plan;

    private ?int $tenantPlanId;

    private string $status;

    private ?\DateTimeInterface $trialEndsAt;

    private ?\DateTimeInterface $subscriptionEndsAt;

    private bool $active;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        DatabaseConfig $databaseConfig,
        ?string $domain = null,
        ?string $logoPath = null,
        ?MailConfig $mailConfig = null,
        ?CacheConfig $cacheConfig = null,
        ?QueueConfig $queueConfig = null,
        ?FeatureFlags $featureFlags = null,
        ?ApiKeys $apiKeys = null,
        ?array $settings = null,
        string $plan = 'free',
        ?int $tenantPlanId = null,
        string $status = 'active',
        ?\DateTimeInterface $trialEndsAt = null,
        ?\DateTimeInterface $subscriptionEndsAt = null,
        bool $active = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->domain = $domain;
        $this->logoPath = $logoPath;
        $this->databaseConfig = $databaseConfig;
        $this->mailConfig = $mailConfig;
        $this->cacheConfig = $cacheConfig;
        $this->queueConfig = $queueConfig;
        $this->featureFlags = $featureFlags ?? new FeatureFlags([]);
        $this->apiKeys = $apiKeys ?? new ApiKeys([]);
        $this->settings = $settings;
        $this->plan = $plan;
        $this->tenantPlanId = $tenantPlanId;
        $this->status = $status;
        $this->trialEndsAt = $trialEndsAt;
        $this->subscriptionEndsAt = $subscriptionEndsAt;
        $this->active = $active;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    // Getters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function getDatabaseConfig(): DatabaseConfig
    {
        return $this->databaseConfig;
    }

    public function getMailConfig(): ?MailConfig
    {
        return $this->mailConfig;
    }

    public function getCacheConfig(): ?CacheConfig
    {
        return $this->cacheConfig;
    }

    public function getQueueConfig(): ?QueueConfig
    {
        return $this->queueConfig;
    }

    public function getFeatureFlags(): FeatureFlags
    {
        return $this->featureFlags;
    }

    public function getApiKeys(): ApiKeys
    {
        return $this->apiKeys;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function getTenantPlanId(): ?int
    {
        return $this->tenantPlanId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTrialEndsAt(): ?\DateTimeInterface
    {
        return $this->trialEndsAt;
    }

    public function getSubscriptionEndsAt(): ?\DateTimeInterface
    {
        return $this->subscriptionEndsAt;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Domain behaviour
    public function updateConfig(array $data): void
    {
        if (array_key_exists('database_config', $data) && is_array($data['database_config'])) {
            $this->databaseConfig = DatabaseConfig::fromArray($data['database_config']);
        }
        if (array_key_exists('mail_config', $data)) {
            $mailConfig = $data['mail_config'];
            $this->mailConfig = is_array($mailConfig) ? MailConfig::fromArray($mailConfig) : null;
        }
        if (array_key_exists('cache_config', $data)) {
            $cacheConfig = $data['cache_config'];
            $this->cacheConfig = is_array($cacheConfig) ? CacheConfig::fromArray($cacheConfig) : null;
        }
        if (array_key_exists('queue_config', $data)) {
            $queueConfig = $data['queue_config'];
            $this->queueConfig = is_array($queueConfig) ? QueueConfig::fromArray($queueConfig) : null;
        }
        if (array_key_exists('feature_flags', $data) && is_array($data['feature_flags'])) {
            $this->featureFlags = new FeatureFlags($data['feature_flags']);
        }
        if (array_key_exists('api_keys', $data) && is_array($data['api_keys'])) {
            $this->apiKeys = new ApiKeys($data['api_keys']);
        }
        if (array_key_exists('settings', $data)) {
            $this->settings = is_array($data['settings']) ? $data['settings'] : null;
        }
        if (isset($data['active'])) {
            $this->active = (bool) $data['active'];
        }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function update(
        string $name,
        string $slug,
        ?string $domain,
        ?string $logoPath,
        DatabaseConfig $databaseConfig,
        ?MailConfig $mailConfig = null,
        ?CacheConfig $cacheConfig = null,
        ?QueueConfig $queueConfig = null,
        ?FeatureFlags $featureFlags = null,
        ?ApiKeys $apiKeys = null,
        ?array $settings = null,
        string $plan = 'free',
        ?int $tenantPlanId = null,
        string $status = 'active',
        ?\DateTimeInterface $trialEndsAt = null,
        ?\DateTimeInterface $subscriptionEndsAt = null,
        bool $active = true
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->domain = $domain;
        $this->logoPath = $logoPath;
        $this->databaseConfig = $databaseConfig;
        $this->mailConfig = $mailConfig;
        $this->cacheConfig = $cacheConfig;
        $this->queueConfig = $queueConfig;
        $this->featureFlags = $featureFlags ?? $this->featureFlags;
        $this->apiKeys = $apiKeys ?? $this->apiKeys;
        $this->settings = $settings;
        $this->plan = $plan;
        $this->tenantPlanId = $tenantPlanId;
        $this->status = $status;
        $this->trialEndsAt = $trialEndsAt;
        $this->subscriptionEndsAt = $subscriptionEndsAt;
        $this->active = $active;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setLogoPath(?string $path): void
    {
        $this->logoPath = $path;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
