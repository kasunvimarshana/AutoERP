<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleHealthCheck extends Command
{
    protected $signature = 'modules:health-check {module?} {--details : Show detailed check results}';
    protected $description = 'Perform health checks on all modules or a specific module';

    private array $modules = [];
    private array $results = [];

    public function handle(): int
    {
        $specificModule = $this->argument('module');

        $this->info('🏥 Module Health Check');
        $this->newLine();

        // Load modules
        $this->loadModules($specificModule);

        if (empty($this->modules)) {
            $this->error('❌ No modules to check');
            return self::FAILURE;
        }

        // Run health checks
        foreach ($this->modules as $module => $config) {
            $this->checkModule($module, $config);
        }

        // Display summary
        $this->displaySummary();

        // Return based on results
        $failed = collect($this->results)->filter(fn($result) => !$result['healthy'])->count();
        
        if ($failed > 0) {
            $this->newLine();
            $this->error("❌ {$failed} module(s) failed health check");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ All modules are healthy!');
        return self::SUCCESS;
    }

    private function loadModules(?string $specificModule): void
    {
        $config = config('modules.registered');

        foreach ($config as $name => $moduleConfig) {
            if ($specificModule && $name !== $specificModule) {
                continue;
            }

            if (!isset($moduleConfig['enabled']) || $moduleConfig['enabled'] !== true) {
                continue;
            }

            $this->modules[$name] = $moduleConfig;
        }

        $count = count($this->modules);
        $this->info($specificModule 
            ? "Checking module: {$specificModule}"
            : "Checking {$count} enabled modules");
        $this->newLine();
    }

    private function checkModule(string $module, array $config): void
    {
        $this->line("🔍 Checking {$module}...");

        $checks = [
            'structure' => $this->checkModuleStructure($module),
            'provider' => $this->checkServiceProvider($module),
            'migrations' => $this->checkMigrations($module),
            'routes' => $this->checkRoutes($module),
            'config' => $this->checkConfig($module),
            'dependencies' => $this->checkDependencies($module, $config),
        ];

        $healthy = !in_array(false, $checks, true);

        $this->results[$module] = [
            'healthy' => $healthy,
            'checks' => $checks,
        ];

        if ($healthy) {
            $this->info("  ✅ All checks passed");
        } else {
            $this->error("  ❌ Some checks failed");
            if ($this->option('details')) {
                foreach ($checks as $check => $passed) {
                    $status = $passed ? '✅' : '❌';
                    $this->line("    {$status} {$check}");
                }
            }
        }
        $this->newLine();
    }

    private function checkModuleStructure(string $module): bool
    {
        $modulePath = base_path("modules/{$module}");
        
        if (!File::isDirectory($modulePath)) {
            if ($this->option('details')) {
                $this->error("    Module directory not found: {$modulePath}");
            }
            return false;
        }

        // Check for essential directories
        $essentialDirs = ['Providers'];
        foreach ($essentialDirs as $dir) {
            if (!File::isDirectory("{$modulePath}/{$dir}")) {
                if ($this->option('details')) {
                    $this->error("    Missing directory: {$dir}");
                }
                return false;
            }
        }

        return true;
    }

    private function checkServiceProvider(string $module): bool
    {
        $providerPath = base_path("modules/{$module}/Providers/{$module}ServiceProvider.php");
        
        if (!File::exists($providerPath)) {
            if ($this->option('details')) {
                $this->error("    Service provider not found: {$providerPath}");
            }
            return false;
        }

        // Check if provider is registered in bootstrap/providers.php
        $providersFile = base_path('bootstrap/providers.php');
        if (!File::exists($providersFile)) {
            return true; // Skip if providers file doesn't exist
        }

        $content = File::get($providersFile);
        $expectedProvider = "Modules\\{$module}\\Providers\\{$module}ServiceProvider::class";
        
        if (strpos($content, $expectedProvider) === false) {
            if ($this->option('details')) {
                $this->error("    Provider not registered in bootstrap/providers.php");
            }
            return false;
        }

        return true;
    }

    private function checkMigrations(string $module): bool
    {
        $migrationsPath = base_path("modules/{$module}/Database/Migrations");
        
        if (!File::isDirectory($migrationsPath)) {
            // Migrations are optional
            return true;
        }

        $migrations = File::files($migrationsPath);
        
        if ($this->option('details') && !empty($migrations)) {
            $this->line("    Found " . count($migrations) . " migration(s)");
        }

        return true;
    }

    private function checkRoutes(string $module): bool
    {
        $routesPath = base_path("modules/{$module}/Routes");
        
        if (!File::isDirectory($routesPath)) {
            // Routes are optional
            return true;
        }

        $routeFiles = File::files($routesPath);
        
        if ($this->option('details') && !empty($routeFiles)) {
            $this->line("    Found " . count($routeFiles) . " route file(s)");
        }

        return true;
    }

    private function checkConfig(string $module): bool
    {
        $configPath = base_path("modules/{$module}/Config");
        
        if (!File::isDirectory($configPath)) {
            // Config is optional
            return true;
        }

        $configFiles = File::files($configPath);
        
        if ($this->option('details') && !empty($configFiles)) {
            $this->line("    Found " . count($configFiles) . " config file(s)");
        }

        return true;
    }

    private function checkDependencies(string $module, array $config): bool
    {
        $dependencies = $config['dependencies'] ?? [];
        
        if (empty($dependencies)) {
            return true;
        }

        $allModules = config('modules.registered');
        
        foreach ($dependencies as $dependency) {
            if (!isset($allModules[$dependency])) {
                if ($this->option('details')) {
                    $this->error("    Missing dependency: {$dependency}");
                }
                return false;
            }

            $depConfig = $allModules[$dependency];
            if (!isset($depConfig['enabled']) || $depConfig['enabled'] !== true) {
                if ($this->option('details')) {
                    $this->error("    Dependency not enabled: {$dependency}");
                }
                return false;
            }
        }

        if ($this->option('details')) {
            $this->line("    All " . count($dependencies) . " dependencies satisfied");
        }

        return true;
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('📊 Health Check Summary');
        $this->newLine();

        $data = [];
        foreach ($this->results as $module => $result) {
            $checks = $result['checks'];
            $passed = count(array_filter($checks));
            $total = count($checks);
            $status = $result['healthy'] ? '✅ Healthy' : '❌ Issues';

            $data[] = [
                $module,
                $status,
                "{$passed}/{$total}",
            ];
        }

        $this->table(['Module', 'Status', 'Checks Passed'], $data);

        // Statistics
        $total = count($this->results);
        $healthy = collect($this->results)->filter(fn($r) => $r['healthy'])->count();
        $unhealthy = $total - $healthy;

        $this->newLine();
        $this->info("Total: {$total} | Healthy: {$healthy} | Issues: {$unhealthy}");
    }
}
