<?php

namespace App\Providers;

use App\Domain\Contracts\AuthServiceInterface;
use App\Domain\Contracts\TenantServiceInterface;
use App\Services\AuthService;
use App\Services\RuntimeConfigService;
use App\Services\TenantService;
use App\Services\TokenService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        // Core services
        $this->app->singleton(RuntimeConfigService::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(TokenService::class);

        // Auth service binding
        $this->app->bind(AuthServiceInterface::class, AuthService::class);

        // Tenant service binding
        $this->app->bind(TenantServiceInterface::class, TenantService::class);
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Add a JSON macro for standardized API responses
        Response::macro('apiSuccess', function (
            mixed $data = null,
            string $message = 'Success',
            int $status = 200
        ) {
            return Response::json([
                'success' => true,
                'message' => $message,
                'data'    => $data,
            ], $status);
        });

        Response::macro('apiError', function (
            string $message = 'An error occurred',
            mixed $errors = null,
            int $status = 400
        ) {
            return Response::json(array_filter([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ]), $status);
        });
    }
}
