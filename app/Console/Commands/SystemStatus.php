<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class SystemStatus extends Command
{
    protected $signature = 'system:status';
    protected $description = 'Display comprehensive system status and health metrics';

    public function handle(): int
    {
        $this->displayHeader();
        $this->displayEnvironmentInfo();
        $this->displayDatabaseInfo();
        $this->displayModuleStatus();
        $this->displayCacheInfo();
        $this->displayQueueInfo();
        $this->displayStorageInfo();
        $this->displaySummary();

        return self::SUCCESS;
    }

    private function displayHeader(): void
    {
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║     Enterprise ERP/CRM SaaS Platform - System Status    ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function displayEnvironmentInfo(): void
    {
        $this->info('📊 Environment Information');
        $this->newLine();

        $data = [
            ['Application', config('app.name')],
            ['Environment', app()->environment()],
            ['Debug Mode', config('app.debug') ? '✅ Enabled' : '❌ Disabled'],
            ['URL', config('app.url')],
            ['Timezone', config('app.timezone')],
            ['Locale', config('app.locale')],
            ['PHP Version', PHP_VERSION],
            ['Laravel Version', app()->version()],
        ];

        $this->table(['Setting', 'Value'], $data);
        $this->newLine();
    }

    private function displayDatabaseInfo(): void
    {
        $this->info('💾 Database Information');
        $this->newLine();

        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");
            
            DB::connection()->getPdo();
            $connected = '✅ Connected';

            // Get database stats
            $tables = $this->getDatabaseTables();
            $totalRecords = $this->getTotalRecords($tables);

            $data = [
                ['Connection', $connection],
                ['Driver', $driver],
                ['Status', $connected],
                ['Total Tables', count($tables)],
                ['Total Records', number_format($totalRecords)],
            ];

            $this->table(['Metric', 'Value'], $data);
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function displayModuleStatus(): void
    {
        $this->info('🔧 Module Status');
        $this->newLine();

        $modules = config('modules.registered', []);
        $enabled = collect($modules)->filter(fn($m) => $m['enabled'] ?? false)->count();
        $disabled = count($modules) - $enabled;

        $data = [
            ['Total Modules', count($modules)],
            ['Enabled', "✅ {$enabled}"],
            ['Disabled', "❌ {$disabled}"],
        ];

        $this->table(['Metric', 'Value'], $data);

        // List enabled modules
        $this->line('  Enabled Modules:');
        foreach ($modules as $name => $config) {
            if ($config['enabled'] ?? false) {
                $priority = $config['priority'] ?? '?';
                $this->line("    [{$priority}] {$name}");
            }
        }

        $this->newLine();
    }

    private function displayCacheInfo(): void
    {
        $this->info('🗄️  Cache Information');
        $this->newLine();

        try {
            $driver = config('cache.default');
            $store = Cache::getStore();
            
            // Test cache
            $testKey = 'system_status_test_' . time();
            Cache::put($testKey, 'test', 5);
            $working = Cache::get($testKey) === 'test';
            Cache::forget($testKey);

            $data = [
                ['Driver', $driver],
                ['Status', $working ? '✅ Working' : '❌ Not Working'],
            ];

            $this->table(['Metric', 'Value'], $data);
        } catch (\Exception $e) {
            $this->error('❌ Cache error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function displayQueueInfo(): void
    {
        $this->info('📬 Queue Information');
        $this->newLine();

        try {
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver");

            $data = [
                ['Connection', $connection],
                ['Driver', $driver],
            ];

            $this->table(['Metric', 'Value'], $data);
        } catch (\Exception $e) {
            $this->error('❌ Queue error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function displayStorageInfo(): void
    {
        $this->info('💿 Storage Information');
        $this->newLine();

        try {
            $disk = config('filesystems.default');
            $storagePath = storage_path();
            $publicPath = public_path();

            $storageSize = $this->getDirectorySize($storagePath);
            $publicSize = $this->getDirectorySize($publicPath);

            $data = [
                ['Default Disk', $disk],
                ['Storage Path', $storagePath],
                ['Storage Size', $this->formatBytes($storageSize)],
                ['Public Path', $publicPath],
                ['Public Size', $this->formatBytes($publicSize)],
            ];

            $this->table(['Metric', 'Value'], $data);
        } catch (\Exception $e) {
            $this->error('❌ Storage error: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function displaySummary(): void
    {
        $this->info('✨ System Health Summary');
        $this->newLine();

        $checks = [
            ['Environment', $this->checkEnvironment()],
            ['Database', $this->checkDatabase()],
            ['Cache', $this->checkCache()],
            ['Storage', $this->checkStorage()],
            ['Modules', $this->checkModules()],
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check[1] === '✅ Healthy');

        $this->table(['Component', 'Status'], $checks);

        $this->newLine();
        if ($allHealthy) {
            $this->info('🎉 All systems operational!');
        } else {
            $this->warn('⚠️  Some components require attention');
        }
        $this->newLine();
    }

    private function getDatabaseTables(): array
    {
        $driver = config('database.default');
        $connection = DB::connection($driver);

        if ($connection->getDriverName() === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_column($tables, 'name');
        }

        if ($connection->getDriverName() === 'mysql') {
            $database = config("database.connections.{$driver}.database");
            $tables = DB::select('SHOW TABLES');
            return array_column(json_decode(json_encode($tables), true), "Tables_in_{$database}");
        }

        return [];
    }

    private function getTotalRecords(array $tables): int
    {
        $total = 0;
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $total += $count;
            } catch (\Exception $e) {
                // Skip tables that can't be counted
            }
        }
        return $total;
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function checkEnvironment(): string
    {
        return app()->environment('production') ? '✅ Healthy' : '⚠️  Development';
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return '✅ Healthy';
        } catch (\Exception $e) {
            return '❌ Unhealthy';
        }
    }

    private function checkCache(): string
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 5);
            $result = Cache::get($testKey) === 'test';
            Cache::forget($testKey);
            return $result ? '✅ Healthy' : '❌ Unhealthy';
        } catch (\Exception $e) {
            return '❌ Unhealthy';
        }
    }

    private function checkStorage(): string
    {
        $storagePath = storage_path();
        return is_writable($storagePath) ? '✅ Healthy' : '❌ Unhealthy';
    }

    private function checkModules(): string
    {
        $modules = config('modules.registered', []);
        $enabled = collect($modules)->filter(fn($m) => $m['enabled'] ?? false)->count();
        return $enabled > 0 ? '✅ Healthy' : '❌ Unhealthy';
    }
}
