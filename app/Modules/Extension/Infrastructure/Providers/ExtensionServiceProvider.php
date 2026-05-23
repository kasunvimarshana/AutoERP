<?php

namespace Modules\Extension\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttachmentRepository;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentCommentRepository;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories\EloquentEntityAttributeRepository;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            AttachmentRepositoryInterface::class => EloquentAttachmentRepository::class,
            CommentRepositoryInterface::class => EloquentCommentRepository::class,
            EntityAttributeRepositoryInterface::class => EloquentEntityAttributeRepository::class,
        ] as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
