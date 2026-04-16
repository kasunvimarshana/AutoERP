<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;

class Tenant
{
    private ?int $id;

    private string $name;

    private ?string $domain;

    private ?string $logoPath;

    private DatabaseConfig $databaseConfig;

    private ?MailConfig $mailConfig;

    private ?CacheConfig $cacheConfig;

    private ?QueueConfig $queueConfig;

    private FeatureFlags $featureFlags;

    private ApiKeys $apiKeys;

    private bool $active;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        string $name,
        DatabaseConfig $databaseConfig,
        ?string $domain = null,
        ?string $logoPath = null,
        ?MailConfig $mailConfig = null,
        ?CacheConfig $cacheConfig = null,
        ?QueueConfig $queueConfig = null,
        ?FeatureFlags $featureFlags = null,
        ?ApiKeys $apiKeys = null,
        bool $active = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->domain = $domain;
        $this->logoPath = $logoPath;
        $this->databaseConfig = $databaseConfig;
        $this->mailConfig = $mailConfig;
        $this->cacheConfig = $cacheConfig;
        $this->queueConfig = $queueConfig;
        $this->featureFlags = $featureFlags ?? new FeatureFlags([]);
        $this->apiKeys = $apiKeys ?? new ApiKeys([]);
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
        if (isset($data['database_config'])) {
            $this->databaseConfig = DatabaseConfig::fromArray($data['database_config']);
        }
        if (isset($data['mail_config'])) {
            $this->mailConfig = MailConfig::fromArray($data['mail_config']);
        }
        if (isset($data['cache_config'])) {
            $this->cacheConfig = CacheConfig::fromArray($data['cache_config']);
        }
        if (isset($data['queue_config'])) {
            $this->queueConfig = QueueConfig::fromArray($data['queue_config']);
        }
        if (isset($data['feature_flags'])) {
            $this->featureFlags = new FeatureFlags($data['feature_flags']);
        }
        if (isset($data['api_keys'])) {
            $this->apiKeys = new ApiKeys($data['api_keys']);
        }
        if (isset($data['active'])) {
            $this->active = (bool) $data['active'];
        }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function update(
        string $name,
        ?string $domain,
        DatabaseConfig $databaseConfig,
        ?MailConfig $mailConfig = null,
        ?CacheConfig $cacheConfig = null,
        ?QueueConfig $queueConfig = null,
        ?FeatureFlags $featureFlags = null,
        ?ApiKeys $apiKeys = null,
        bool $active = true
    ): void {
        $this->name = $name;
        $this->domain = $domain;
        $this->databaseConfig = $databaseConfig;
        $this->mailConfig = $mailConfig;
        $this->cacheConfig = $cacheConfig;
        $this->queueConfig = $queueConfig;
        $this->featureFlags = $featureFlags ?? $this->featureFlags;
        $this->apiKeys = $apiKeys ?? $this->apiKeys;
        $this->active = $active;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setLogoPath(?string $path): void
    {
        $this->logoPath = $path;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
