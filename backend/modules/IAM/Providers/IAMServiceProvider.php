<?php

namespace Modules\IAM\Providers;

use Illuminate\Support\Facades\Route;
use Modules\Core\Abstracts\BaseModuleServiceProvider;

class IAMServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleId = 'iam';

    protected string $moduleName = 'Identity & Access Management';

    protected string $moduleVersion = '1.0.0';

    protected array $dependencies = ['core'];

    public function boot(): void
    {
        $this->registerRoutes();
        $this->loadModuleMigrations();
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api', 'tenant.identify'],
            'prefix' => 'api/iam',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/iam.php',
            'iam'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleConfig(): array
    {
        return [
            'entities' => [
                'users' => [
                    'name' => 'Users',
                    'singular' => 'User',
                    'icon' => 'users',
                    'routes' => [
                        'list' => '/iam/users',
                        'create' => '/iam/users/create',
                        'edit' => '/iam/users/{id}/edit',
                        'view' => '/iam/users/{id}',
                    ],
                ],
                'roles' => [
                    'name' => 'Roles',
                    'singular' => 'Role',
                    'icon' => 'shield-check',
                    'routes' => [
                        'list' => '/iam/roles',
                        'create' => '/iam/roles/create',
                        'edit' => '/iam/roles/{id}/edit',
                        'view' => '/iam/roles/{id}',
                    ],
                ],
                'permissions' => [
                    'name' => 'Permissions',
                    'singular' => 'Permission',
                    'icon' => 'lock-closed',
                    'routes' => [
                        'list' => '/iam/permissions',
                        'create' => '/iam/permissions/create',
                        'edit' => '/iam/permissions/{id}/edit',
                        'view' => '/iam/permissions/{id}',
                    ],
                ],
            ],
            'features' => [
                'rbac' => true,
                'abac' => false,
                'mfa' => true,
                'sso' => true,
                'oauth2' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return [
            'iam.users.view',
            'iam.users.create',
            'iam.users.update',
            'iam.users.delete',
            'iam.users.impersonate',
            'iam.roles.view',
            'iam.roles.create',
            'iam.roles.update',
            'iam.roles.delete',
            'iam.permissions.view',
            'iam.permissions.create',
            'iam.permissions.update',
            'iam.permissions.delete',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/api/iam/users',
                'name' => 'iam.users.index',
                'permission' => 'iam.users.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/iam/users',
                'name' => 'iam.users.store',
                'permission' => 'iam.users.create',
            ],
            [
                'method' => 'GET',
                'path' => '/api/iam/roles',
                'name' => 'iam.roles.index',
                'permission' => 'iam.roles.view',
            ],
            [
                'method' => 'POST',
                'path' => '/api/iam/roles',
                'name' => 'iam.roles.store',
                'permission' => 'iam.roles.create',
            ],
        ];
    }

    public function provides(): array
    {
        return [];
    }
}
