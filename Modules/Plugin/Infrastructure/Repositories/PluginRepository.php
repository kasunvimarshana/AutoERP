<?php

declare(strict_types=1);

namespace Modules\Plugin\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Plugin\Domain\Contracts\PluginRepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;

/**
 * Plugin repository implementation.
 *
 * Extends the AbstractRepository.
 * PluginManifest is global (no tenant scope), so queries are unscoped.
 */
class PluginRepository extends AbstractRepository implements PluginRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = PluginManifest::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByAlias(string $alias): ?PluginManifest
    {
        /** @var PluginManifest|null */
        return PluginManifest::query()->where('alias', $alias)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findActivePlugins(): Collection
    {
        return PluginManifest::query()->where('active', true)->get();
    }
}
