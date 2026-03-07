<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Saga\SagaOrchestrator::class);
    }

    public function boot(): void
    {
        // Enforce HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Global query logging in debug mode
        if (config('app.debug') && !app()->runningInConsole()) {
            \Illuminate\Support\Facades\DB::listen(function ($query) {
                \Illuminate\Support\Facades\Log::channel('daily')->debug('SQL', [
                    'sql'      => $query->sql,
                    'bindings' => $query->bindings,
                    'time'     => $query->time,
                ]);
            });
        }
    }
}
