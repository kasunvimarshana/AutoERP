<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use Illuminate\Support\Str;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Platform\TenantSchemaCompatibilityService;
use Psr\Log\LoggerInterface;
use Throwable;

final class TenantSubscriptionQueryService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly TenantSchemaCompatibilityService $schema,
        private readonly LoggerInterface $logger,
    ) {}

    public function current(int $tenantId): Result
    {
        return $this->query($tenantId, 'current', fn () => $this->subscriptions->findCurrentByTenant($tenantId));
    }

    public function history(int $tenantId, int $perPage, int $page): Result
    {
        return $this->query(
            $tenantId,
            'history',
            fn () => $this->subscriptions->pageHistory($tenantId, $perPage, $page),
        );
    }

    private function query(int $tenantId, string $operation, callable $query): Result
    {
        if ($this->tenants->findById($tenantId) === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
        }

        $schema = $this->schema->inspect();
        if (! $schema['compatible']) {
            return Result::failure(new Error(
                TenantErrorCode::SCHEMA_INCOMPATIBLE,
                'The deployed database schema is not compatible with tenant subscription management.',
                $schema,
            ));
        }

        try {
            return Result::success($this->executionContext->runForTenant($tenantId, $query));
        } catch (Throwable $exception) {
            $correlationId = (string) Str::uuid();
            $this->logger->error('Tenant subscription query failed.', [
                'tenant_id' => $tenantId,
                'operation' => $operation,
                'correlation_id' => $correlationId,
                'exception' => $exception,
            ]);

            return Result::failure(new Error(
                TenantErrorCode::SUBSCRIPTION_DATA_UNAVAILABLE,
                'Tenant subscription data could not be loaded.',
                ['correlation_id' => $correlationId, 'operation' => $operation],
            ));
        }
    }
}
