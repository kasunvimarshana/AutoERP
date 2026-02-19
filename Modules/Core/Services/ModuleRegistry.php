<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Modules\Core\Contracts\ModuleInterface;

/**
 * ModuleRegistry
 *
 * Manages module registration, discovery, and lifecycle
 */
class ModuleRegistry
{
    /**
     * @var Collection<ModuleInterface>
     */
    protected Collection $modules;

    /**
     * @var array<string, bool>
     */
    protected array $enabledModules = [];

    public function __construct()
    {
        $this->modules = collect();
        $this->loadModuleConfiguration();
    }

    /**
     * Register a module
     */
    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    /**
     * Get a registered module
     */
    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->get($name);
    }

    /**
     * Get all registered modules
     *
     * @return Collection<ModuleInterface>
     */
    public function all(): Collection
    {
        return $this->modules;
    }

    /**
     * Get enabled modules only
     *
     * @return Collection<ModuleInterface>
     */
    public function enabled(): Collection
    {
        return $this->modules->filter(fn (ModuleInterface $module) => $module->isEnabled());
    }

    /**
     * Check if a module is enabled
     */
    public function isEnabled(string $name): bool
    {
        return $this->enabledModules[$name] ?? false;
    }

    /**
     * Enable a module
     */
    public function enable(string $name): void
    {
        $this->enabledModules[$name] = true;
        $this->saveModuleConfiguration();
    }

    /**
     * Disable a module
     */
    public function disable(string $name): void
    {
        $this->enabledModules[$name] = false;
        $this->saveModuleConfiguration();
    }

    /**
     * Load module configuration from storage
     */
    protected function loadModuleConfiguration(): void
    {
        $configPath = config_path('modules.php');
        if (file_exists($configPath)) {
            $this->enabledModules = require $configPath;
        }
    }

    /**
     * Save module configuration to storage
     */
    protected function saveModuleConfiguration(): void
    {
        $configPath = config_path('modules.php');
        $content = "<?php\n\nreturn ".var_export($this->enabledModules, true).";\n";
        file_put_contents($configPath, $content);
    }

    /**
     * Validate module dependencies
     */
    public function validateDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            if (! $this->isEnabled($dependency)) {
                return false;
            }
        }

        return true;
    }
}
