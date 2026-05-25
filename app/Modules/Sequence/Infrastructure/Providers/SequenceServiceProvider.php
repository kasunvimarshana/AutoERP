<?php

declare(strict_types=1);

namespace Modules\Sequence\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\CreateSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\DeleteSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\GetSequenceServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\ListSequencesServiceInterface;
use Modules\Sequence\Application\Contracts\UseCases\Sequences\UpdateSequenceServiceInterface;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Application\UseCases\Sequences\CreateSequenceService;
use Modules\Sequence\Application\UseCases\Sequences\DeleteSequenceService;
use Modules\Sequence\Application\UseCases\Sequences\GetSequenceService;
use Modules\Sequence\Application\UseCases\Sequences\ListSequencesService;
use Modules\Sequence\Application\UseCases\Sequences\UpdateSequenceService;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;
use Modules\Sequence\Domain\Services\SequenceDomainService;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Models\SequenceModel;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Repositories\EloquentSequenceRepository;

final class SequenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/sequence.php', 'sequence');

        $this->app->singleton(SequenceDomainServiceInterface::class, SequenceDomainService::class);

        foreach (
            [
                ListSequencesServiceInterface::class => ListSequencesService::class,
                GetSequenceServiceInterface::class => GetSequenceService::class,
                CreateSequenceServiceInterface::class => CreateSequenceService::class,
                UpdateSequenceServiceInterface::class => UpdateSequenceService::class,
                DeleteSequenceServiceInterface::class => DeleteSequenceService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(SequenceRepositoryInterface::class, function (): SequenceRepositoryInterface {
            return new EloquentSequenceRepository(new SequenceModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
