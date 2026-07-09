<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ApplicationKeyConfiguration;
use Illuminate\Console\Command;

final class ProductionReadinessCommand extends Command
{
    protected $signature = 'production:readiness';

    protected $description = 'Fail when runtime configuration is unsafe for production.';

    public function handle(): int
    {
        $failures = [];

        $this->assertTrue($this->laravel->environment('production'), 'APP_ENV must be production.', $failures);
        $this->assertFalse((bool) config('app.debug'), 'APP_DEBUG must be false.', $failures);
        $this->assertApplicationKey($failures);
        $this->assertPublicHttpsUrl((string) config('app.url'), 'APP_URL', $failures);
        $this->assertPublicHttpsUrl((string) env('PLATFORM_PUBLIC_URL'), 'PLATFORM_PUBLIC_URL', $failures);
        $this->assertTrue((bool) config('module-auth.cookies.tenant_refresh.secure'), 'Tenant refresh cookies must be secure.', $failures);
        $this->assertTrue((bool) config('module-auth.cookies.platform_refresh.secure'), 'Platform refresh cookies must be secure.', $failures);
        $this->assertFalse($this->envBool('TENANT_LOCAL_FALLBACK_ENABLED', false), 'TENANT_LOCAL_FALLBACK_ENABLED must be false.', $failures);
        $this->assertFalse(in_array((string) config('database.default'), ['sqlite'], true), 'DB_CONNECTION must not be sqlite.', $failures);
        $this->assertFalse(in_array((string) config('queue.default'), ['sync'], true), 'QUEUE_CONNECTION must not be sync.', $failures);
        $this->assertFalse(in_array((string) config('cache.default'), ['array'], true), 'CACHE_STORE must not be array.', $failures);
        $this->assertFalse(in_array((string) config('session.driver'), ['array', 'file'], true), 'SESSION_DRIVER must not be array or file.', $failures);
        $this->assertTrue((bool) config('session.encrypt'), 'SESSION_ENCRYPT must be true.', $failures);
        $this->assertPrivateDisk((string) env('PRIVATE_OBJECT_DEFAULT_DISK'), 'PRIVATE_OBJECT_DEFAULT_DISK', $failures);
        $this->assertPrivateDisk((string) env('TENANT_DOCUMENT_DISK'), 'TENANT_DOCUMENT_DISK', $failures);
        $this->assertPrivateDisk((string) env('VEHICLE_SERVICE_DOCUMENT_DISK'), 'VEHICLE_SERVICE_DOCUMENT_DISK', $failures);
        $this->assertNotOneOf(strtolower((string) env('LOG_LEVEL', 'debug')), ['debug'], 'LOG_LEVEL must not be debug.', $failures);

        if ($failures !== []) {
            $this->error('Production readiness failed:');
            foreach ($failures as $failure) {
                $this->line(' - '.$failure);
            }

            return self::FAILURE;
        }

        $this->info('Production readiness configuration checks passed.');

        return self::SUCCESS;
    }

    /** @param list<string> $failures */
    private function assertApplicationKey(array &$failures): void
    {
        $key = trim((string) config('app.key'));
        $cipher = (string) config('app.cipher');
        if (! ApplicationKeyConfiguration::isValid($key, $cipher)) {
            $failures[] = 'APP_KEY must be a valid production key for the configured cipher.';
        }
    }

    /** @param list<string> $failures */
    private function assertPublicHttpsUrl(string $value, string $name, array &$failures): void
    {
        $host = parse_url($value, PHP_URL_HOST);
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (! is_string($host) || ! is_string($scheme)) {
            $failures[] = $name.' must be a valid absolute HTTPS URL.';

            return;
        }

        $normalizedHost = strtolower($host);
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', 'example.com'];
        if ($scheme !== 'https' || in_array($normalizedHost, $blockedHosts, true) || str_ends_with($normalizedHost, '.example.com')) {
            $failures[] = $name.' must use a real public HTTPS host.';
        }
    }

    /** @param list<string> $failures */
    private function assertPrivateDisk(string $value, string $name, array &$failures): void
    {
        if ($value === '' || in_array($value, ['public', 'local'], true)) {
            $failures[] = $name.' must point to a private storage disk.';

            return;
        }

        $disk = config('filesystems.disks.'.$value);
        if (! is_array($disk)) {
            $failures[] = $name.' references an undefined filesystem disk.';

            return;
        }

        if (($disk['visibility'] ?? null) !== 'private' || ($disk['serve'] ?? false) !== false) {
            $failures[] = $name.' must reference a non-served private filesystem disk.';
        }
    }

    /** @param list<string> $failures */
    private function assertTrue(bool $condition, string $message, array &$failures): void
    {
        if (! $condition) {
            $failures[] = $message;
        }
    }

    /** @param list<string> $failures */
    private function assertFalse(bool $condition, string $message, array &$failures): void
    {
        if ($condition) {
            $failures[] = $message;
        }
    }

    /**
     * @param list<string> $blocked
     * @param list<string> $failures
     */
    private function assertNotOneOf(string $value, array $blocked, string $message, array &$failures): void
    {
        if (in_array($value, $blocked, true)) {
            $failures[] = $message;
        }
    }

    private function envBool(string $key, bool $default): bool
    {
        return filter_var(env($key, $default), FILTER_VALIDATE_BOOL);
    }
}
