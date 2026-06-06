<?php

declare(strict_types=1);

namespace Modules\Audit\Entities;

use Modules\Core\Entities\Entity;

final class AuditLogEntry extends Entity
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        int|string $id,
        private readonly ?int $tenantId,
        private readonly ?int $organizationUnitId,
        private readonly ?int $userId,
        private readonly string $event,
        private readonly string $auditableType,
        private readonly string $auditableId,
        private readonly ?array $oldValues,
        private readonly ?array $newValues,
        private readonly ?array $metadata,
        private readonly ?string $url,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly ?array $tags,
        private readonly ?string $occurredAt,
    ) {
        parent::__construct($id);
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function organizationUnitId(): ?int
    {
        return $this->organizationUnitId;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function event(): string
    {
        return $this->event;
    }

    public function auditableType(): string
    {
        return $this->auditableType;
    }

    public function auditableId(): string
    {
        return $this->auditableId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function oldValues(): ?array
    {
        return $this->oldValues;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function newValues(): ?array
    {
        return $this->newValues;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @return array<int, string>|null
     */
    public function tags(): ?array
    {
        return $this->tags;
    }

    public function occurredAt(): ?string
    {
        return $this->occurredAt;
    }
}
