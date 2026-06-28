<?php

declare(strict_types=1);

namespace Modules\Sequence\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sequence\Models\SequenceModel;
use Modules\Sequence\Repositories\EloquentSequenceRepository;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;
use Modules\Sequence\Services\Rules\SequenceDomainService;

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
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
