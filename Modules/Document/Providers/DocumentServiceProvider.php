<?php

declare(strict_types=1);

namespace Modules\Document\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentTag;
use Modules\Document\Models\Folder;
use Modules\Document\Policies\DocumentPolicy;
use Modules\Document\Policies\DocumentTagPolicy;
use Modules\Document\Policies\FolderPolicy;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module configuration
        $this->mergeConfigFrom(__DIR__.'/../Config/document.php', 'document');

        // Register repositories
        $this->app->singleton(\Modules\Document\Repositories\DocumentRepository::class);
        $this->app->singleton(\Modules\Document\Repositories\FolderRepository::class);
        $this->app->singleton(\Modules\Document\Repositories\DocumentVersionRepository::class);
        $this->app->singleton(\Modules\Document\Repositories\DocumentTagRepository::class);
        $this->app->singleton(\Modules\Document\Repositories\DocumentShareRepository::class);

        // Register services
        $this->app->singleton(\Modules\Document\Services\DocumentStorageService::class);
        $this->app->singleton(\Modules\Document\Services\DocumentVersionService::class);
        $this->app->singleton(\Modules\Document\Services\FolderService::class);
        $this->app->singleton(\Modules\Document\Services\DocumentShareService::class);
        $this->app->singleton(\Modules\Document\Services\DocumentSearchService::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register policies
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);
        Gate::policy(DocumentTag::class, DocumentTagPolicy::class);

        // Register event listeners (if Audit module is available)
        if (config('audit.enabled', false)) {
            $this->registerEventListeners();
        }

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/document.php' => config_path('document.php'),
            ], 'document-config');
        }
    }

    /**
     * Register event listeners for audit logging
     */
    private function registerEventListeners(): void
    {
        if (class_exists(\Modules\Document\Events\DocumentUploaded::class)) {
            Event::listen(
                \Modules\Document\Events\DocumentUploaded::class,
                function ($event) {
                    activity()
                        ->performedOn($event->document)
                        ->causedBy(auth()->user())
                        ->withProperties(['document_id' => $event->document->id])
                        ->log('Document uploaded');
                }
            );
        }

        if (class_exists(\Modules\Document\Events\DocumentDeleted::class)) {
            Event::listen(
                \Modules\Document\Events\DocumentDeleted::class,
                function ($event) {
                    activity()
                        ->performedOn($event->document)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'document_id' => $event->document->id,
                            'permanent' => $event->permanent,
                        ])
                        ->log('Document deleted');
                }
            );
        }

        if (class_exists(\Modules\Document\Events\DocumentShared::class)) {
            Event::listen(
                \Modules\Document\Events\DocumentShared::class,
                function ($event) {
                    activity()
                        ->performedOn($event->share->document)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'document_id' => $event->share->document_id,
                            'shared_with' => $event->share->user_id,
                            'permission' => $event->share->permission_type->value,
                        ])
                        ->log('Document shared');
                }
            );
        }

        if (class_exists(\Modules\Document\Events\VersionCreated::class)) {
            Event::listen(
                \Modules\Document\Events\VersionCreated::class,
                function ($event) {
                    activity()
                        ->performedOn($event->version->document)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'document_id' => $event->version->document_id,
                            'version_number' => $event->version->version_number,
                        ])
                        ->log('Document version created');
                }
            );
        }
    }
}
