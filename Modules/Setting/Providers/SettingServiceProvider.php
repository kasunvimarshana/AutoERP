<?php
namespace Modules\Setting\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Setting\Domain\Contracts\SettingRepositoryInterface;
use Modules\Setting\Infrastructure\Repositories\SettingRepository;
class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'setting');
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
