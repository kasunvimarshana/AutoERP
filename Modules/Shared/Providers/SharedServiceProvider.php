<?php
namespace Modules\Shared\Providers;
use Illuminate\Support\ServiceProvider;
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf('current.tenant.id', fn () => null);
        $this->app->bindIf('current.tenant', fn () => null);
    }
    public function boot(): void {}
}
