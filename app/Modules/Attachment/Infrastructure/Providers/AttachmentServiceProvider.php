<?php

namespace Modules\Attachment\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Attachment\Application\Contracts\DeleteAttachmentServiceInterface;
use Modules\Attachment\Application\Contracts\FindAttachmentServiceInterface;
use Modules\Attachment\Application\Contracts\GetAttachmentsServiceInterface;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Application\Services\DeleteAttachmentService;
use Modules\Attachment\Application\Services\FindAttachmentService;
use Modules\Attachment\Application\Services\GetAttachmentsService;
use Modules\Attachment\Application\Services\UploadAttachmentService;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
use Modules\Attachment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttachmentRepository;

class AttachmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttachmentRepositoryInterface::class, EloquentAttachmentRepository::class);
        $this->app->bind(UploadAttachmentServiceInterface::class, UploadAttachmentService::class);
        $this->app->bind(DeleteAttachmentServiceInterface::class, DeleteAttachmentService::class);
        $this->app->bind(GetAttachmentsServiceInterface::class, GetAttachmentsService::class);
        $this->app->bind(FindAttachmentServiceInterface::class, FindAttachmentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
