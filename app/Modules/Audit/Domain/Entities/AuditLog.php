<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Entities;

use DateTimeInterface;

class AuditLog
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $userId,
        public readonly string $event,
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly ?array $oldValues,
        public readonly ?array $newValues,
        public readonly ?string $url,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?array $tags,
        public readonly DateTimeInterface $createdAt,
    ) {}

    public function getDiff(): array
    {
        if ($this->oldValues === null && $this->newValues === null) {
            return [];
        }

        $old = $this->oldValues ?? [];
        $new = $this->newValues ?? [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $diff = [];
        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;
            if ($oldVal !== $newVal) {
                $diff[$key] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        return $diff;
    }

    public function hasChanges(): bool
    {
        return $this->getDiff() !== [];
    }
}
