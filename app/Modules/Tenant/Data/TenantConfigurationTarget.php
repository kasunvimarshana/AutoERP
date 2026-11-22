<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Data;

final readonly class TenantConfigurationTarget
{
    public function __construct(
        public int $id,
        public string $status,
    ) {}

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
