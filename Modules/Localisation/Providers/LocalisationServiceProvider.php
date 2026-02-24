<?php

namespace Modules\Localisation\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Localisation\Domain\Contracts\LanguagePackRepositoryInterface;
use Modules\Localisation\Domain\Contracts\LocalePreferenceRepositoryInterface;
use Modules\Localisation\Infrastructure\Repositories\LanguagePackRepository;
use Modules\Localisation\Infrastructure\Repositories\LocalePreferenceRepository;

class LocalisationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LanguagePackRepositoryInterface::class, LanguagePackRepository::class);
        $this->app->bind(LocalePreferenceRepositoryInterface::class, LocalePreferenceRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'localisation');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
