<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\EntityAttributeModel;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttachmentRepository;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentCommentRepository;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentEntityAttributeRepository;

final class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/extension.php', 'extension');
        $this->app->singleton(AttachmentRepositoryInterface::class, function (): AttachmentRepositoryInterface {
            return new EloquentAttachmentRepository(new AttachmentModel);
        });
        $this->app->singleton(EntityAttributeRepositoryInterface::class, function (): EntityAttributeRepositoryInterface {
            return new EloquentEntityAttributeRepository(new EntityAttributeModel);
        });
        $this->app->singleton(CommentRepositoryInterface::class, function (): CommentRepositoryInterface {
            return new EloquentCommentRepository(new CommentModel);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
