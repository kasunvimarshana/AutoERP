<?php

namespace App\Repositories;

use App\Models\MetadataFeatureFlag;
use Illuminate\Database\Eloquent\Collection;

class MetadataFeatureFlagRepository extends BaseRepository
{
    protected function model(): string
    {
        return MetadataFeatureFlag::class;
    }

    /**
     * Check if a feature is enabled.
     */
    public function isEnabled(string $name): bool
    {
        $flag = $this->model
            ->where('name', $name)
            ->where('is_enabled', true)
            ->first();

        return $flag !== null;
    }

    /**
     * Get all enabled features.
     */
    public function getEnabled(): Collection
    {
        return $this->model
            ->enabled()
            ->get();
    }

    /**
     * Get features by module.
     */
    public function getByModule(string $module): Collection
    {
        return $this->model
            ->where('module', $module)
            ->get();
    }

    /**
     * Enable a feature.
     */
    public function enable(string $name): bool
    {
        $flag = $this->model->where('name', $name)->first();

        if ($flag) {
            $flag->enable();

            return true;
        }

        return false;
    }

    /**
     * Disable a feature.
     */
    public function disable(string $name): bool
    {
        $flag = $this->model->where('name', $name)->first();

        if ($flag) {
            $flag->disable();

            return true;
        }

        return false;
    }
}
