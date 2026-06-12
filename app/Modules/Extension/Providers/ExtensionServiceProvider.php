<?php

declare(strict_types=1);

namespace Modules\Extension\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Models\CommentModel;
use Modules\Extension\Models\EntityAttributeModel;
use Modules\Extension\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Repositories\CommentRepositoryInterface;
use Modules\Extension\Repositories\EloquentAttachmentRepository;
use Modules\Extension\Repositories\EloquentCommentRepository;
use Modules\Extension\Repositories\EloquentEntityAttributeRepository;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;

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
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
