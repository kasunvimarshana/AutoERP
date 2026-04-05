<?php
declare(strict_types=1);
namespace Modules\Attachment\Infrastructure\Providers;
use Illuminate\Support\ServiceProvider;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Application\Services\UploadAttachmentService;
use Modules\Attachment\Domain\RepositoryInterfaces\AttachmentRepositoryInterface;
use Modules\Attachment\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;
use Modules\Attachment\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttachmentRepository;
class AttachmentServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(AttachmentRepositoryInterface::class, fn($app) => new EloquentAttachmentRepository($app->make(AttachmentModel::class)));
        $this->app->bind(UploadAttachmentServiceInterface::class, fn($app) => new UploadAttachmentService($app->make(AttachmentRepositoryInterface::class)));
    }
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
