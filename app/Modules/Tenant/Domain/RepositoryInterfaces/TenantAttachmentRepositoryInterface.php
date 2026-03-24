<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantAttachment;

interface TenantAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?TenantAttachment;

    public function getByTenant(int $tenantId, ?string $type = null): Collection;

    public function save(TenantAttachment $attachment): TenantAttachment;
}
