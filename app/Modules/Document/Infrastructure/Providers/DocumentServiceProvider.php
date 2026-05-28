<?php

namespace Modules\Document\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Document\Application\Contracts\SequenceServiceInterface;
use Modules\Document\Application\Queries\GetDocumentQuery;
use Modules\Document\Application\Queries\ListDocumentsQuery;
use Modules\Document\Domain\Repositories\DocumentDefinitionRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentItemDefinitionRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentTypeRepositoryInterface;
use Modules\Document\Domain\Repositories\DocumentWorkflowRepositoryInterface;
use Modules\Document\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentDefinitionRepository;
use Modules\Document\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentItemDefinitionRepository;
use Modules\Document\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentRepository;
use Modules\Document\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentTypeRepository;
use Modules\Document\Infrastructure\Persistence\Eloquent\Repositories\EloquentDocumentWorkflowRepository;
use Modules\Document\Infrastructure\Services\SequenceService;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/document.php', 'document');

        $this->app->singleton(DocumentRepositoryInterface::class, EloquentDocumentRepository::class);
        $this->app->singleton(
            DocumentDefinitionRepositoryInterface::class,
            EloquentDocumentDefinitionRepository::class,
        );
        $this->app->singleton(
            DocumentItemDefinitionRepositoryInterface::class,
            EloquentDocumentItemDefinitionRepository::class,
        );
        $this->app->singleton(DocumentTypeRepositoryInterface::class, EloquentDocumentTypeRepository::class);
        $this->app->singleton(DocumentWorkflowRepositoryInterface::class, EloquentDocumentWorkflowRepository::class);
        $this->app->singleton(SequenceServiceInterface::class, SequenceService::class);
        $this->app->singleton(ListDocumentsQuery::class, ListDocumentsQuery::class);
        $this->app->singleton(GetDocumentQuery::class, GetDocumentQuery::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/API/Routes/api.php');
    }
}
