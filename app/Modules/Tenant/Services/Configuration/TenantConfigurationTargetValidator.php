<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Configuration;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Configuration\Contracts\ConfigurationTargetValidatorInterface;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantModel;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TenantConfigurationTargetValidator implements ConfigurationTargetValidatorInterface
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    public function assertTenantReadable(int $tenantId): void
    {
        if ($tenantId < 1 || $this->tenants->findById($tenantId) === null) {
            throw (new ModelNotFoundException())->setModel(TenantModel::class, [$tenantId]);
        }
    }

    public function assertTenantWritable(int $tenantId): void
    {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            throw (new ModelNotFoundException())->setModel(TenantModel::class, [$tenantId]);
        }

        if ((string) $tenant->get('status') === TenantStatus::ARCHIVED) {
            throw new ConflictHttpException('Archived tenant configuration is read-only.');
        }
    }
}
