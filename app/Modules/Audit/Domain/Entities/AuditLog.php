<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Entities;

use Modules\Audit\Domain\ValueObjects\AuditAction;

class AuditLog
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?int $tenantId,
        private readonly ?int $userId,
        private readonly AuditAction $event,
        private readonly string $auditableType,
        private readonly int|string $auditableId,
        private readonly ?array $oldValues,
        private readonly ?array $newValues,
        private readonly ?string $url,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly ?array $tags,
        private readonly ?array $metadata,
        private readonly \DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getEvent(): AuditAction
    {
        return $this->event;
    }

    public function getAuditableType(): string
    {
        return $this->auditableType;
    }

    public function getAuditableId(): int|string
    {
        return $this->auditableId;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function hasChanges(): bool
    {
        return $this->oldValues !== null || $this->newValues !== null;
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getDiff(): array
    {
        $diff = [];
        $keys = array_unique(array_merge(
            array_keys($this->oldValues ?? []),
            array_keys($this->newValues ?? []),
        ));

        foreach ($keys as $key) {
            $old = $this->oldValues[$key] ?? null;
            $new = $this->newValues[$key] ?? null;

            if ($old !== $new) {
                $diff[$key] = ['old' => $old, 'new' => $new];
            }
        }

        return $diff;
    }
}
