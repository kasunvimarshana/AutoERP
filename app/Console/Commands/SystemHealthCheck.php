<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * SystemHealthCheck Command
 *
 * Performs comprehensive health check of the ERP/CRM system
 * Validates configuration, modules, dependencies, and database
 */
class SystemHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:health-check 
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive system health check';

    /**
     * Health check results
     */
    protected array $results = [
        'passed' => [],
        'failed' => [],
        'warnings' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting System Health Check...');
        $this->newLine();

        // Run all health checks
        $this->checkEnvironmentConfiguration();
        $this->checkModuleConfiguration();
        $this->checkDatabaseConnection();
        $this->checkRequiredExtensions();
        $this->checkFilePermissions();
        $this->checkQueueConfiguration();
        $this->checkSecuritySettings();
        $this->checkModuleRegistration();

        // Display results
        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputSummary();
        }

        // Return exit code based on failures
        return empty($this->results['failed']) ? 0 : 1;
    }

    /**
     * Check environment configuration
     */
    protected function checkEnvironmentConfiguration(): void
    {
        $this->info('📋 Checking Environment Configuration...');

        // Check if .env file exists
        if (! File::exists(base_path('.env'))) {
            $this->recordFailure('.env file not found');

            return;
        }
        $this->recordPass('.env file exists');

        // Check APP_KEY
        if (empty(config('app.key'))) {
            $this->recordFailure('APP_KEY is not set');
        } else {
            $this->recordPass('APP_KEY is configured');
        }

        // Check APP_ENV
        $env = config('app.env');
        if (in_array($env, ['local', 'staging', 'production'])) {
            $this->recordPass("APP_ENV is set to '{$env}'");
        } else {
            $this->recordWarning("APP_ENV value '{$env}' may not be standard");
        }

        // Check APP_DEBUG (should be false in production)
        if (config('app.env') === 'production' && config('app.debug')) {
            $this->recordFailure('APP_DEBUG should be false in production');
        } else {
            $this->recordPass('APP_DEBUG configuration is appropriate');
        }

        // Check JWT_SECRET
        if (empty(config('jwt.secret'))) {
            $this->recordFailure('JWT_SECRET is not set');
        } else {
            $this->recordPass('JWT_SECRET is configured');
        }

        $this->newLine();
    }

    /**
     * Check module configuration
     */
    protected function checkModuleConfiguration(): void
    {
        $this->info('🧩 Checking Module Configuration...');

        $modules = config('modules.registered', []);

        if (empty($modules)) {
            $this->recordFailure('No modules registered');

            return;
        }

        $this->recordPass(count($modules).' modules registered');

        // Check each module
        foreach ($modules as $name => $config) {
            $modulePath = config('modules.modules_path').'/'.$name;

            if (! File::isDirectory($modulePath)) {
                $this->recordFailure("Module directory not found: {$name}");

                continue;
            }

            // Check service provider
            $namespace = config('modules.namespace');
            $providerClass = "{$namespace}\\{$name}\\Providers\\{$name}ServiceProvider";

            if (! class_exists($providerClass)) {
                $this->recordWarning("Service provider not found: {$providerClass}");
            }
        }

        $this->newLine();
    }

    /**
     * Check database connection
     */
    protected function checkDatabaseConnection(): void
    {
        $this->info('💾 Checking Database Connection...');

        try {
            \DB::connection()->getPdo();
            $driver = config('database.default');
            $this->recordPass("Database connection successful ({$driver})");

            // Check if migrations are run
            if (\Schema::hasTable('migrations')) {
                $count = \DB::table('migrations')->count();
                $this->recordPass("{$count} migrations have been run");
            } else {
                $this->recordWarning('Migrations table not found - database may not be initialized');
            }
        } catch (\Exception $e) {
            $this->recordFailure('Database connection failed: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Check required PHP extensions
     */
    protected function checkRequiredExtensions(): void
    {
        $this->info('🔧 Checking Required PHP Extensions...');

        $required = ['bcmath', 'pdo', 'mbstring', 'openssl', 'json', 'curl'];

        foreach ($required as $extension) {
            if (extension_loaded($extension)) {
                $this->recordPass("Extension '{$extension}' is loaded");
            } else {
                $this->recordFailure("Required extension '{$extension}' is not loaded");
            }
        }

        // Check PHP version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.2.0', '>=')) {
            $this->recordPass("PHP version {$phpVersion} meets requirement (8.2+)");
        } else {
            $this->recordFailure("PHP version {$phpVersion} does not meet requirement (8.2+)");
        }

        $this->newLine();
    }

    /**
     * Check file permissions
     */
    protected function checkFilePermissions(): void
    {
        $this->info('📁 Checking File Permissions...');

        $writableDirs = [
            'storage',
            'storage/app',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];

        foreach ($writableDirs as $dir) {
            $path = base_path($dir);
            if (is_writable($path)) {
                $this->recordPass("Directory '{$dir}' is writable");
            } else {
                $this->recordFailure("Directory '{$dir}' is not writable");
            }
        }

        $this->newLine();
    }

    /**
     * Check queue configuration
     */
    protected function checkQueueConfiguration(): void
    {
        $this->info('⚙️ Checking Queue Configuration...');

        $queueDriver = config('queue.default');
        $this->recordPass("Queue driver is set to '{$queueDriver}'");

        if ($queueDriver !== 'sync') {
            // Check if queue table exists
            if (\Schema::hasTable('jobs')) {
                $this->recordPass('Queue jobs table exists');
            } else {
                $this->recordWarning('Queue jobs table not found - run migrations');
            }
        }

        $this->newLine();
    }

    /**
     * Check security settings
     */
    protected function checkSecuritySettings(): void
    {
        $this->info('🔒 Checking Security Settings...');

        // Check if in production
        if (config('app.env') === 'production') {
            // APP_DEBUG should be false
            if (config('app.debug') === false) {
                $this->recordPass('APP_DEBUG is disabled in production');
            } else {
                $this->recordFailure('APP_DEBUG should be disabled in production');
            }

            // URL should be HTTPS
            if (str_starts_with(config('app.url'), 'https://')) {
                $this->recordPass('APP_URL uses HTTPS');
            } else {
                $this->recordWarning('APP_URL should use HTTPS in production');
            }
        }

        // Check JWT secret strength
        $jwtSecret = config('jwt.secret');
        if (strlen($jwtSecret) >= 32) {
            $this->recordPass('JWT_SECRET has adequate length');
        } else {
            $this->recordWarning('JWT_SECRET should be at least 32 characters');
        }

        $this->newLine();
    }

    /**
     * Check module registration
     */
    protected function checkModuleRegistration(): void
    {
        $this->info('📦 Checking Module Registration...');

        $expectedModules = [
            'Core', 'Tenant', 'Auth', 'Audit', 'Product', 'Pricing',
            'CRM', 'Sales', 'Purchase', 'Inventory', 'Accounting',
            'Billing', 'Notification', 'Reporting', 'Document', 'Workflow',
        ];

        $registeredModules = array_keys(config('modules.registered', []));

        foreach ($expectedModules as $module) {
            if (in_array($module, $registeredModules)) {
                $this->recordPass("Module '{$module}' is registered");
            } else {
                $this->recordWarning("Module '{$module}' is not registered");
            }
        }

        $this->newLine();
    }

    /**
     * Record a passed check
     */
    protected function recordPass(string $message): void
    {
        $this->results['passed'][] = $message;

        if (! $this->option('json')) {
            $this->line("  <fg=green>✓</> {$message}");
        }
    }

    /**
     * Record a failed check
     */
    protected function recordFailure(string $message): void
    {
        $this->results['failed'][] = $message;

        if (! $this->option('json')) {
            $this->line("  <fg=red>✗</> {$message}");
        }
    }

    /**
     * Record a warning
     */
    protected function recordWarning(string $message): void
    {
        $this->results['warnings'][] = $message;

        if (! $this->option('json')) {
            $this->line("  <fg=yellow>⚠</> {$message}");
        }
    }

    /**
     * Output summary
     */
    protected function outputSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('                    SUMMARY                            ');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $passed = count($this->results['passed']);
        $failed = count($this->results['failed']);
        $warnings = count($this->results['warnings']);
        $total = $passed + $failed + $warnings;

        $this->line("  <fg=green>✓ Passed:</> {$passed}");
        $this->line("  <fg=red>✗ Failed:</> {$failed}");
        $this->line("  <fg=yellow>⚠ Warnings:</> {$warnings}");
        $this->line("  <fg=blue>Total Checks:</> {$total}");

        $this->newLine();

        if (empty($this->results['failed'])) {
            $this->info('🎉 System health check completed successfully!');
        } else {
            $this->error('⚠️  System health check found issues that need attention.');
        }

        $this->newLine();
    }

    /**
     * Output results as JSON
     */
    protected function outputJson(): void
    {
        $output = [
            'timestamp' => now()->toIso8601String(),
            'status' => empty($this->results['failed']) ? 'healthy' : 'unhealthy',
            'summary' => [
                'passed' => count($this->results['passed']),
                'failed' => count($this->results['failed']),
                'warnings' => count($this->results['warnings']),
                'total' => count($this->results['passed']) + count($this->results['failed']) + count($this->results['warnings']),
            ],
            'details' => $this->results,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }
}
