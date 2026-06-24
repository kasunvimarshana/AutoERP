<?php

declare(strict_types=1);

namespace Modules\Tenant\Queue;

use Closure;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class RestoreTenantJobContext
{
    public function __construct(private readonly TenantExecutionContextInterface $executionContext) {}

    public function handle(object $job, Closure $next): mixed
    {
        if (! $job instanceof TenantAwareJobInterface) {
            throw new LogicException(sprintf(
                'Queue middleware [%s] requires jobs to implement [%s].',
                self::class,
                TenantAwareJobInterface::class,
            ));
        }

        $context = $job->tenantJobContext();

        return $this->executionContext->runForTenant(
            $context->tenantId,
            static fn (): mixed => $next($job),
        );
    }
}
