<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Audit\Application\Contracts\AuditServiceInterface;

trait ResolvesAuditContext
{
    protected function resolveAuditService(): ?AuditServiceInterface
    {
        try {
            return app(AuditServiceInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditTenantId(): ?int
    {
        try {
            return Auth::user()?->tenant_id ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUrl(): ?string
    {
        try {
            return app()->runningInConsole() ? 'console' : request()->fullUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditIpAddress(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveAuditUserAgent(): ?string
    {
        try {
            return app()->runningInConsole() ? null : request()->userAgent();
        } catch (\Throwable) {
            return null;
        }
    }
}
