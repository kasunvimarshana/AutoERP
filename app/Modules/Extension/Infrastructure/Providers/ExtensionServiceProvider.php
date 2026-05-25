<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Extension\Application\Contracts\UseCases\Attachments\CreateAttachmentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Attachments\DeleteAttachmentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Attachments\GetAttachmentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Attachments\ListAttachmentsServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Attachments\UpdateAttachmentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Comments\CreateCommentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Comments\DeleteCommentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Comments\GetCommentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Comments\ListCommentsServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\Comments\UpdateCommentServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\EntityAttributes\CreateEntityAttributeServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\EntityAttributes\DeleteEntityAttributeServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\EntityAttributes\GetEntityAttributeServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\EntityAttributes\ListEntityAttributesServiceInterface;
use Modules\Extension\Application\Contracts\UseCases\EntityAttributes\UpdateEntityAttributeServiceInterface;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Application\UseCases\Attachments\CreateAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\DeleteAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\GetAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\ListAttachmentsService;
use Modules\Extension\Application\UseCases\Attachments\UpdateAttachmentService;
use Modules\Extension\Application\UseCases\Comments\CreateCommentService;
use Modules\Extension\Application\UseCases\Comments\DeleteCommentService;
use Modules\Extension\Application\UseCases\Comments\GetCommentService;
use Modules\Extension\Application\UseCases\Comments\ListCommentsService;
use Modules\Extension\Application\UseCases\Comments\UpdateCommentService;
use Modules\Extension\Application\UseCases\EntityAttributes\CreateEntityAttributeService;
use Modules\Extension\Application\UseCases\EntityAttributes\DeleteEntityAttributeService;
use Modules\Extension\Application\UseCases\EntityAttributes\GetEntityAttributeService;
use Modules\Extension\Application\UseCases\EntityAttributes\ListEntityAttributesService;
use Modules\Extension\Application\UseCases\EntityAttributes\UpdateEntityAttributeService;
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
        $this->mergeConfigFrom(__DIR__ . '/../Config/extension.php', 'extension');

        foreach (
            [
                ListAttachmentsServiceInterface::class => ListAttachmentsService::class,
                GetAttachmentServiceInterface::class => GetAttachmentService::class,
                CreateAttachmentServiceInterface::class => CreateAttachmentService::class,
                UpdateAttachmentServiceInterface::class => UpdateAttachmentService::class,
                DeleteAttachmentServiceInterface::class => DeleteAttachmentService::class,
                ListEntityAttributesServiceInterface::class => ListEntityAttributesService::class,
                GetEntityAttributeServiceInterface::class => GetEntityAttributeService::class,
                CreateEntityAttributeServiceInterface::class => CreateEntityAttributeService::class,
                UpdateEntityAttributeServiceInterface::class => UpdateEntityAttributeService::class,
                DeleteEntityAttributeServiceInterface::class => DeleteEntityAttributeService::class,
                ListCommentsServiceInterface::class => ListCommentsService::class,
                GetCommentServiceInterface::class => GetCommentService::class,
                CreateCommentServiceInterface::class => CreateCommentService::class,
                UpdateCommentServiceInterface::class => UpdateCommentService::class,
                DeleteCommentServiceInterface::class => DeleteCommentService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(AttachmentRepositoryInterface::class, function (): AttachmentRepositoryInterface {
            return new EloquentAttachmentRepository(new AttachmentModel());
        });
        $this->app->singleton(EntityAttributeRepositoryInterface::class, function (): EntityAttributeRepositoryInterface {
            return new EloquentEntityAttributeRepository(new EntityAttributeModel());
        });
        $this->app->singleton(CommentRepositoryInterface::class, function (): CommentRepositoryInterface {
            return new EloquentCommentRepository(new CommentModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Eloquent/Migrations');
    }
}