<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;

interface TenantAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?TenantAttachment;

    /**
     * @return iterable<int, TenantAttachment>
     */
    public function getByTenant(int $tenantId, ?string $type = null): iterable;

    public function save(TenantAttachment $attachment): TenantAttachment;
}
