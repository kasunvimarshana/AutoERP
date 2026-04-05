<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\POS\Application\Services\CloseSessionService;
use Modules\POS\Application\Services\OpenSessionService;
use Modules\POS\Application\Services\ProcessPosTransactionService;
use Modules\POS\Application\Services\VoidPosTransactionService;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTerminalRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTransactionRepositoryInterface;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosSessionModel;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTerminalModel;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTransactionLineModel;
use Modules\POS\Infrastructure\Persistence\Eloquent\Models\PosTransactionModel;
use Modules\POS\Infrastructure\Persistence\Eloquent\Repositories\EloquentPosSessionRepository;
use Modules\POS\Infrastructure\Persistence\Eloquent\Repositories\EloquentPosTerminalRepository;
use Modules\POS\Infrastructure\Persistence\Eloquent\Repositories\EloquentPosTransactionRepository;

class POSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PosTerminalRepositoryInterface::class, fn($app) =>
            new EloquentPosTerminalRepository($app->make(PosTerminalModel::class))
        );
        $this->app->bind(PosSessionRepositoryInterface::class, fn($app) =>
            new EloquentPosSessionRepository($app->make(PosSessionModel::class))
        );
        $this->app->bind(PosTransactionRepositoryInterface::class, fn($app) =>
            new EloquentPosTransactionRepository(
                $app->make(PosTransactionModel::class),
                $app->make(PosTransactionLineModel::class),
            )
        );

        $this->app->bind(OpenSessionService::class, fn($app) =>
            new OpenSessionService(
                $app->make(PosTerminalRepositoryInterface::class),
                $app->make(PosSessionRepositoryInterface::class),
            )
        );
        $this->app->bind(CloseSessionService::class, fn($app) =>
            new CloseSessionService($app->make(PosSessionRepositoryInterface::class))
        );
        $this->app->bind(ProcessPosTransactionService::class, fn($app) =>
            new ProcessPosTransactionService(
                $app->make(PosSessionRepositoryInterface::class),
                $app->make(PosTransactionRepositoryInterface::class),
            )
        );
        $this->app->bind(VoidPosTransactionService::class, fn($app) =>
            new VoidPosTransactionService($app->make(PosTransactionRepositoryInterface::class))
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
