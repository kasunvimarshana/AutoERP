<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Interfaces\Http\Middleware\EnforceJsonMiddleware;
use Modules\Core\Interfaces\Http\Middleware\ResolveTenantMiddleware;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind a default null tenant context (use bind, not instance, so the
        // container can resolve it even when no tenant is active)
        $this->app->bind('current.tenant.id', fn () => null);
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('tenant', ResolveTenantMiddleware::class);
        $this->app['router']->aliasMiddleware('json', EnforceJsonMiddleware::class);
    }
}
