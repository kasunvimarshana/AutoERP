<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\IAM\Entities\Group;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Entities\Role;
use Modules\IAM\Policies\GroupPolicy;
use Modules\IAM\Policies\PermissionPolicy;
use Modules\IAM\Policies\RolePolicy;
use Modules\IAM\Repositories\Contracts\GroupRepositoryInterface;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;
use Modules\IAM\Repositories\Contracts\RoleRepositoryInterface;
use Modules\IAM\Repositories\Contracts\UserRepositoryInterface;
use Modules\IAM\Repositories\GroupRepository;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\RoleRepository;
use Modules\IAM\Repositories\UserRepository;

class IAMServiceProvider extends ServiceProvider
{
    /**
     * Module namespace.
     */
    protected string $moduleName = 'IAM';

    /**
     * Module namespace alias.
     */
    protected string $moduleNameLower = 'iam';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Register config
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );

        // Register repositories
        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        $this->app->bind(
            GroupRepositoryInterface::class,
            GroupRepository::class
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->publishConfig();
        $this->registerPolicies();
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
    }

    /**
     * Register module migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Publish module config.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
