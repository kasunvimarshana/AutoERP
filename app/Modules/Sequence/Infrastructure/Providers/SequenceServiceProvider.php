<?php

declare(strict_types=1);

namespace Modules\Sequence\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Modules\Sequence\Domain\Services\SequenceDomainService;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Models\SequenceModel;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Repositories\EloquentSequenceRepository;

final class SequenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/sequence.php', 'sequence');

        $this->app->singleton(SequenceDomainServiceInterface::class, SequenceDomainService::class);
        $this->app->singleton(SequenceRepositoryInterface::class, function (): SequenceRepositoryInterface {
            return new EloquentSequenceRepository(new SequenceModel);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migrations');
    }
}
