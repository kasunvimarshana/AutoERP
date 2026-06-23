<?php

declare(strict_types=1);

namespace Modules\Core\Models\Concerns;

use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;

trait HasImmutableTenantOwnership
{
    protected static function bootHasImmutableTenantOwnership(): void
    {
        static::creating(static function (self $model): void {
            $tenantId = $model->getAttribute('tenant_id');
            if (! is_numeric($tenantId) || (int) $tenantId < 1) {
                throw new LogicException(sprintf(
                    'Tenant-owned table [%s] requires a valid tenant_id.',
                    $model->getTable(),
                ));
            }

            self::assertExecutionTenantMatches($model, (int) $tenantId);
        });

        static::updating(static function (self $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException(sprintf(
                    'Tenant ownership of [%s] cannot be changed after creation.',
                    $model->getTable(),
                ));
            }

            self::assertExecutionTenantMatches($model, (int) $model->getAttribute('tenant_id'));
        });

        static::deleting(static function (self $model): void {
            self::assertExecutionTenantMatches($model, (int) $model->getAttribute('tenant_id'));
        });
    }

    private static function assertExecutionTenantMatches(self $model, int $modelTenantId): void
    {
        if (! app()->bound(TenantExecutionContextInterface::class)) {
            throw new LogicException(sprintf(
                'Tenant execution context is required while writing table [%s].',
                $model->getTable(),
            ));
        }

        $executionContext = app(TenantExecutionContextInterface::class);
        $executionTenantId = $executionContext->tenantId();

        if ($executionTenantId !== null) {
            if ($executionTenantId !== $modelTenantId) {
                throw new LogicException(sprintf(
                    'Tenant context mismatch while writing table [%s].',
                    $model->getTable(),
                ));
            }

            return;
        }

        if ($executionContext->isControlPlane()) {
            return;
        }

        throw new LogicException(sprintf(
            'Tenant execution context is required while writing table [%s].',
            $model->getTable(),
        ));
    }
}
