<?php

namespace Modules\Sequence\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sequence\Application\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Infrastructure\Persistence\Eloquent\Repositories\EloquentSequenceRepository;

class SequenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (
            [
                SequenceRepositoryInterface::class => EloquentSequenceRepository::class,
            ] as $interface => $implementation
        ) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}
