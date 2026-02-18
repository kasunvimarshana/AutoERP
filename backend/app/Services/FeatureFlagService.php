<?php

namespace App\Services;

use App\Models\MetadataFeatureFlag;
use App\Repositories\MetadataFeatureFlagRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService extends BaseService
{
    protected MetadataFeatureFlagRepository $repository;

    public function __construct(MetadataFeatureFlagRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Check if a feature is enabled.
     */
    public function isEnabled(string $name): bool
    {
        $cacheKey = "feature:flag:{$name}";

        return Cache::remember($cacheKey, 3600, function () use ($name) {
            return $this->repository->isEnabled($name);
        });
    }

    /**
     * Get all enabled features.
     */
    public function getEnabledFeatures(): Collection
    {
        $cacheKey = 'feature:flags:enabled';

        return Cache::remember($cacheKey, 3600, function () {
            return $this->repository->getEnabled();
        });
    }

    /**
     * Get features by module.
     */
    public function getModuleFeatures(string $module): Collection
    {
        $cacheKey = "feature:flags:module:{$module}";

        return Cache::remember($cacheKey, 3600, function () use ($module) {
            return $this->repository->getByModule($module);
        });
    }

    /**
     * Enable a feature.
     */
    public function enable(string $name): bool
    {
        return $this->transaction(function () use ($name) {
            $result = $this->repository->enable($name);

            if ($result) {
                Cache::forget("feature:flag:{$name}");
                Cache::forget('feature:flags:enabled');

                $this->logInfo("Feature enabled: {$name}");
            }

            return $result;
        });
    }

    /**
     * Disable a feature.
     */
    public function disable(string $name): bool
    {
        return $this->transaction(function () use ($name) {
            $result = $this->repository->disable($name);

            if ($result) {
                Cache::forget("feature:flag:{$name}");
                Cache::forget('feature:flags:enabled');

                $this->logInfo("Feature disabled: {$name}");
            }

            return $result;
        });
    }

    /**
     * Create a feature flag.
     */
    public function create(array $data): MetadataFeatureFlag
    {
        return $this->transaction(function () use ($data) {
            $flag = $this->repository->create($data);

            Cache::forget("feature:flag:{$flag->name}");
            Cache::forget('feature:flags:enabled');

            $this->logInfo("Feature flag created: {$flag->name}");

            return $flag;
        });
    }

    /**
     * Update a feature flag.
     */
    public function update(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $flag = $this->repository->find($id);

            if (! $flag) {
                return false;
            }

            $result = $this->repository->update($id, $data);

            Cache::forget("feature:flag:{$flag->name}");
            Cache::forget('feature:flags:enabled');

            $this->logInfo("Feature flag updated: {$flag->name}");

            return $result;
        });
    }

    /**
     * Check multiple features at once.
     */
    public function checkFeatures(array $features): array
    {
        $result = [];

        foreach ($features as $feature) {
            $result[$feature] = $this->isEnabled($feature);
        }

        return $result;
    }
}
