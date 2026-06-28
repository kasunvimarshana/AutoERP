<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Targets;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Configuration\Contracts\ConfigurationTargetValidatorInterface;
use Modules\Tenant\Data\TenantConfigurationTarget;
use Modules\Tenant\Services\Contracts\TenantConfigurationTargetReaderInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TenantBackedConfigurationTargetValidator implements ConfigurationTargetValidatorInterface
{
    public function __construct(
        private readonly TenantConfigurationTargetReaderInterface $targets,
    ) {}

    public function assertTenantReadable(int $tenantId): void
    {
        $this->requireTarget($tenantId);
    }

    public function assertTenantWritable(int $tenantId): void
    {
        $target = $this->requireTarget($tenantId);
        if ($target->isArchived()) {
            throw new ConflictHttpException('Archived tenant configuration is read-only.');
        }
    }

    private function requireTarget(int $tenantId): TenantConfigurationTarget
    {
        $target = $this->targets->find($tenantId);
        if ($target === null) {
            throw (new ModelNotFoundException())->setModel('tenant', [$tenantId]);
        }

        return $target;
    }
}
