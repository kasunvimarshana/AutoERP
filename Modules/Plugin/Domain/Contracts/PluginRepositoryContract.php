<?php

declare(strict_types=1);

namespace Modules\Plugin\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;
use Modules\Plugin\Domain\Entities\PluginManifest;

/**
 * Plugin repository contract.
 *
 * Extends the base repository contract with plugin-specific query methods.
 */
interface PluginRepositoryContract extends RepositoryContract
{
    /**
     * Find a plugin manifest by its unique alias.
     */
    public function findByAlias(string $alias): ?PluginManifest;

    /**
     * Return all active plugin manifests.
     *
     * @return Collection<int, PluginManifest>
     */
    public function findActivePlugins(): Collection;
}
