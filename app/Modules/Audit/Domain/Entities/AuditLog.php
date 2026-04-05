<?php
declare(strict_types=1);
namespace Modules\Audit\Domain\Entities;

/**
 * Immutable audit log entry representing a state change on any entity.
 * Events: created | updated | deleted | restored | login | logout | custom
 */
class AuditLog
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $tenantId,
        private readonly ?int $userId,
        private readonly string $event,         // created|updated|deleted|restored|login|logout|custom
        private readonly string $entityType,    // e.g. 'Product', 'SalesOrder'
        private readonly ?string $entityId,     // string to support UUID/int PKs
        private readonly ?array $oldValues,
        private readonly ?array $newValues,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
        private readonly ?string $url,
        private readonly \DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getEvent(): string { return $this->event; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): ?string { return $this->entityId; }
    public function getOldValues(): ?array { return $this->oldValues; }
    public function getNewValues(): ?array { return $this->newValues; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getUrl(): ?string { return $this->url; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    /** Returns a diff showing only changed fields (old → new). */
    public function getDiff(): array
    {
        if ($this->oldValues === null || $this->newValues === null) {
            return [];
        }
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($this->oldValues), array_keys($this->newValues)));
        foreach ($allKeys as $key) {
            $old = $this->oldValues[$key] ?? null;
            $new = $this->newValues[$key] ?? null;
            if ($old !== $new) {
                $diff[$key] = ['old' => $old, 'new' => $new];
            }
        }
        return $diff;
    }
}
