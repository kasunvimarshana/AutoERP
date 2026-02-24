<?php

namespace Modules\DocumentManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\DocumentManagement\Domain\Contracts\DocumentCategoryRepositoryInterface;
use Modules\DocumentManagement\Domain\Contracts\DocumentRepositoryInterface;
use Modules\DocumentManagement\Infrastructure\Repositories\DocumentCategoryRepository;
use Modules\DocumentManagement\Infrastructure\Repositories\DocumentRepository;

class DocumentManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentCategoryRepositoryInterface::class, DocumentCategoryRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'document_management');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
