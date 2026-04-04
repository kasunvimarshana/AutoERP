<?php
namespace Modules\GS1\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\GS1\Application\Contracts\CreateGS1BarcodeServiceInterface;
use Modules\GS1\Application\Contracts\GenerateGS1LabelServiceInterface;
use Modules\GS1\Application\Services\CreateGS1BarcodeService;
use Modules\GS1\Application\Services\GenerateGS1LabelService;
use Modules\GS1\Domain\RepositoryInterfaces\GS1BarcodeRepositoryInterface;
use Modules\GS1\Domain\RepositoryInterfaces\GS1LabelRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGS1BarcodeRepository;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories\EloquentGS1LabelRepository;

class GS1ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GS1BarcodeRepositoryInterface::class, EloquentGS1BarcodeRepository::class);
        $this->app->bind(GS1LabelRepositoryInterface::class, EloquentGS1LabelRepository::class);
        $this->app->bind(CreateGS1BarcodeServiceInterface::class, CreateGS1BarcodeService::class);
        $this->app->bind(GenerateGS1LabelServiceInterface::class, GenerateGS1LabelService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
