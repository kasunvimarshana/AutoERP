<?php

namespace Modules\Recruitment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;
use Modules\Recruitment\Infrastructure\Repositories\JobApplicationRepository;
use Modules\Recruitment\Infrastructure\Repositories\JobPositionRepository;

class RecruitmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(JobPositionRepositoryInterface::class, JobPositionRepository::class);
        $this->app->bind(JobApplicationRepositoryInterface::class, JobApplicationRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'recruitment');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
