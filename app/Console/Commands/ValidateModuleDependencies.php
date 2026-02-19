<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateModuleDependencies extends Command
{
    protected $signature = 'modules:validate-dependencies';
    protected $description = 'Validate module dependencies for circular references and proper priority ordering';

    private array $modules = [];
    private array $dependencies = [];
    private array $visiting = [];
    private array $visited = [];

    public function handle(): int
    {
        $this->info('🔍 Validating Module Dependencies...');
        $this->newLine();

        // Load module configuration
        if (!$this->loadModuleConfig()) {
            return self::FAILURE;
        }

        // Check for circular dependencies
        $this->info('📊 Checking for circular dependencies...');
        if ($circularDeps = $this->detectCircularDependencies()) {
            $this->error('❌ Circular dependencies detected:');
            foreach ($circularDeps as $cycle) {
                $this->error('   → ' . implode(' → ', $cycle));
            }
            return self::FAILURE;
        }
        $this->info('✅ No circular dependencies found');
        $this->newLine();

        // Validate priority ordering
        $this->info('📊 Validating priority ordering...');
        if ($violations = $this->validatePriorityOrdering()) {
            $this->error('❌ Priority ordering violations found:');
            foreach ($violations as $violation) {
                $this->error('   → ' . $violation);
            }
            return self::FAILURE;
        }
        $this->info('✅ Priority ordering is correct');
        $this->newLine();

        // Validate module providers are registered
        $this->info('📊 Validating service provider registration...');
        if ($missing = $this->validateProviderRegistration()) {
            $this->error('❌ Missing service provider registrations:');
            foreach ($missing as $provider) {
                $this->error('   → ' . $provider);
            }
            return self::FAILURE;
        }
        $this->info('✅ All service providers registered');
        $this->newLine();

        // Display dependency graph
        $this->displayDependencyGraph();

        $this->newLine();
        $this->info('✅ All module dependency validations passed!');
        $this->newLine();

        // Display statistics
        $this->displayStatistics();

        return self::SUCCESS;
    }

    private function loadModuleConfig(): bool
    {
        $configPath = config_path('modules.php');
        
        if (!File::exists($configPath)) {
            $this->error("❌ Module configuration file not found: {$configPath}");
            return false;
        }

        $config = config('modules.registered');
        
        if (empty($config)) {
            $this->error('❌ Module configuration is empty');
            return false;
        }

        foreach ($config as $name => $moduleConfig) {
            if (!isset($moduleConfig['enabled']) || $moduleConfig['enabled'] !== true) {
                continue;
            }

            $this->modules[$name] = [
                'priority' => $moduleConfig['priority'] ?? 999,
                'dependencies' => $moduleConfig['dependencies'] ?? [],
                'provides' => $moduleConfig['provides'] ?? [],
            ];

            $this->dependencies[$name] = $moduleConfig['dependencies'] ?? [];
        }

        $this->info("✅ Loaded " . count($this->modules) . " enabled modules");
        
        return true;
    }

    private function detectCircularDependencies(): array
    {
        $cycles = [];

        foreach (array_keys($this->modules) as $module) {
            $this->visiting = [];
            $this->visited = [];
            
            if ($cycle = $this->detectCycle($module, [])) {
                $cycles[] = $cycle;
            }
        }

        return $cycles;
    }

    private function detectCycle(string $module, array $path): ?array
    {
        if (in_array($module, $this->visiting)) {
            // Circular dependency found
            $cycleStart = array_search($module, $path);
            return array_slice($path, $cycleStart);
        }

        if (in_array($module, $this->visited)) {
            return null;
        }

        $this->visiting[] = $module;
        $path[] = $module;

        foreach ($this->dependencies[$module] ?? [] as $dependency) {
            if ($cycle = $this->detectCycle($dependency, $path)) {
                return $cycle;
            }
        }

        $this->visited[] = $module;
        array_pop($path);
        
        $key = array_search($module, $this->visiting);
        if ($key !== false) {
            unset($this->visiting[$key]);
        }

        return null;
    }

    private function validatePriorityOrdering(): array
    {
        $violations = [];

        foreach ($this->modules as $module => $config) {
            $modulePriority = $config['priority'];

            foreach ($config['dependencies'] as $dependency) {
                if (!isset($this->modules[$dependency])) {
                    $violations[] = "Module '{$module}' depends on '{$dependency}' which is not enabled";
                    continue;
                }

                $dependencyPriority = $this->modules[$dependency]['priority'];

                if ($dependencyPriority >= $modulePriority) {
                    $violations[] = sprintf(
                        "Module '%s' (priority %d) depends on '%s' (priority %d) but should have lower priority",
                        $module,
                        $modulePriority,
                        $dependency,
                        $dependencyPriority
                    );
                }
            }
        }

        return $violations;
    }

    private function validateProviderRegistration(): array
    {
        $providersPath = base_path('bootstrap/providers.php');
        
        if (!File::exists($providersPath)) {
            return ['bootstrap/providers.php file not found'];
        }

        $providersContent = File::get($providersPath);
        $missing = [];

        foreach ($this->modules as $module => $config) {
            $expectedProvider = "Modules\\{$module}\\Providers\\{$module}ServiceProvider::class";
            
            if (strpos($providersContent, $expectedProvider) === false) {
                $missing[] = $expectedProvider;
            }
        }

        return $missing;
    }

    private function displayDependencyGraph(): void
    {
        $this->info('📊 Module Dependency Graph:');
        $this->newLine();

        // Sort modules by priority
        $sortedModules = $this->modules;
        uasort($sortedModules, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($sortedModules as $module => $config) {
            $priority = $config['priority'];
            $dependencies = $config['dependencies'];

            if (empty($dependencies)) {
                $this->line(sprintf('  [%2d] %s (no dependencies)', $priority, $module));
            } else {
                $this->line(sprintf('  [%2d] %s', $priority, $module));
                foreach ($dependencies as $dependency) {
                    $depPriority = $this->modules[$dependency]['priority'] ?? '??';
                    $this->line(sprintf('       └─ [%2d] %s', $depPriority, $dependency));
                }
            }
        }

        $this->newLine();
    }

    private function displayStatistics(): void
    {
        $totalModules = count($this->modules);
        $totalDependencies = array_sum(array_map('count', $this->dependencies));
        $maxDepth = $this->calculateMaxDepth();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Modules', $totalModules],
                ['Total Dependencies', $totalDependencies],
                ['Average Dependencies per Module', round($totalDependencies / max($totalModules, 1), 2)],
                ['Maximum Dependency Depth', $maxDepth],
            ]
        );
    }

    private function calculateMaxDepth(): int
    {
        $maxDepth = 0;

        foreach (array_keys($this->modules) as $module) {
            $depth = $this->getModuleDepth($module);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    private function getModuleDepth(string $module, array $visited = []): int
    {
        if (in_array($module, $visited)) {
            return 0; // Circular reference protection
        }

        $visited[] = $module;
        $dependencies = $this->dependencies[$module] ?? [];

        if (empty($dependencies)) {
            return 0;
        }

        $maxDependencyDepth = 0;

        foreach ($dependencies as $dependency) {
            $depth = $this->getModuleDepth($dependency, $visited);
            $maxDependencyDepth = max($maxDependencyDepth, $depth);
        }

        return $maxDependencyDepth + 1;
    }
}
