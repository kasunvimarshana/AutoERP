<?php

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

abstract class BaseService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    protected function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    protected function dispatchEvent($event): void
    {
        Event::dispatch($event);
    }

    protected function getTenantId(): ?int
    {
        return $this->tenantContext->getTenantId();
    }

    protected function hasTenant(): bool
    {
        return $this->tenantContext->hasTenant();
    }

    protected function validateTenant(): void
    {
        if (! $this->hasTenant()) {
            throw new \RuntimeException('No tenant context available');
        }
    }

    protected function logActivity(string $action, Model $model, array $properties = []): void
    {
        if (method_exists($model, 'logActivity')) {
            $model->logActivity($action, $properties);
        }
    }
}
