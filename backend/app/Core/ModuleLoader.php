<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * ModuleLoader - Loads and manages modular components
 * 
 * This class implements the pluggable module system where modules can be
 * independently loaded, validated, and registered with the application.
 */
class ModuleLoader
{
    private string $modulesPath;
    private Collection $modules;
    private array $loadedModules = [];

    public function __construct(string $modulesPath = null)
    {
        $this->modulesPath = $modulesPath ?? base_path('modules');
        $this->modules = collect();
    }

    /**
     * Discover all available modules
     */
    public function discover(): Collection
    {
        if (!File::isDirectory($this->modulesPath)) {
            throw new InvalidArgumentException("Modules directory not found: {$this->modulesPath}");
        }

        $directories = File::directories($this->modulesPath);

        foreach ($directories as $directory) {
            $manifestPath = $directory . '/module.json';
            
            if (File::exists($manifestPath)) {
                $manifest = json_decode(File::get($manifestPath), true);
                
                if (json_last_error() === JSON_ERROR_NONE && $this->validateManifest($manifest)) {
                    $manifest['path'] = $directory;
                    $this->modules->push($manifest);
                }
            }
        }

        // Sort by priority
        $this->modules = $this->modules->sortBy(function ($module) {
            return $module['extra']['priority'] ?? 999;
        });

        return $this->modules;
    }

    /**
     * Validate module manifest structure
     */
    private function validateManifest(array $manifest): bool
    {
        $required = ['name', 'version', 'dependencies'];
        
        foreach ($required as $field) {
            if (!isset($manifest[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get enabled modules only
     */
    public function getEnabledModules(): Collection
    {
        return $this->modules->filter(function ($module) {
            return ($module['extra']['enabled'] ?? false) === true;
        });
    }

    /**
     * Load a specific module by name
     */
    public function load(string $moduleName): bool
    {
        if (isset($this->loadedModules[$moduleName])) {
            return true; // Already loaded
        }

        $module = $this->modules->firstWhere('name', $moduleName);

        if (!$module) {
            throw new InvalidArgumentException("Module not found: {$moduleName}");
        }

        // Check and load dependencies first
        foreach ($module['dependencies'] as $dependency) {
            if (!$this->load($dependency)) {
                throw new InvalidArgumentException("Failed to load dependency: {$dependency} for module: {$moduleName}");
            }
        }

        // Load the module
        $this->loadedModules[$moduleName] = $module;
        
        return true;
    }

    /**
     * Load all enabled modules
     */
    public function loadAll(): void
    {
        $enabledModules = $this->getEnabledModules();

        foreach ($enabledModules as $module) {
            try {
                $this->load($module['name']);
            } catch (\Exception $e) {
                // Log error but continue loading other modules
                logger()->error("Failed to load module {$module['name']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get loaded modules
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Get module by name
     */
    public function getModule(string $name): ?array
    {
        return $this->modules->firstWhere('name', $name);
    }

    /**
     * Check if module is loaded
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->loadedModules[$name]);
    }

    /**
     * Get all modules
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }
}
