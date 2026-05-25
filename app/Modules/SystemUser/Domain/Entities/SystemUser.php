<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Entities;

use InvalidArgumentException;
use Modules\Core\Domain\Entities\Entity;
use Modules\SystemUser\Domain\Constants\SystemUserStatus;

final class SystemUser extends Entity
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int $tenantId,
        private readonly ?int $organizationUnitId,
        private readonly ?int $userId,
        private readonly string $status,
        private readonly ?string $code,
        private readonly ?string $registrationNumber,
        private readonly ?string $notes,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if ($this->tenantId < 1) {
            throw new InvalidArgumentException('Tenant id is required.');
        }

        if (! SystemUserStatus::isValid($this->status)) {
            throw new InvalidArgumentException('Unsupported system user status.');
        }
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function status(): string
    {
        return $this->status;
    }
}
