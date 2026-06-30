<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use PHPUnit\Framework\TestCase;

final class ApplicationBootstrapContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_setup_preserves_existing_application_keys(): void
    {
        $composer = $this->composerConfiguration();
        $setup = $composer['scripts']['setup'] ?? [];

        self::assertContains('@php artisan app:key:ensure --no-interaction', $setup);
        self::assertNotContains('@php artisan key:generate', $setup);
    }

    public function test_development_startup_clears_config_and_passes_readiness_before_processes_start(): void
    {
        $composer = $this->composerConfiguration();
        $runtimeCheck = $composer['scripts']['runtime:check'] ?? [];
        $development = $composer['scripts']['dev'] ?? [];

        self::assertSame([
            '@php artisan config:clear --ansi',
            '@php artisan auth:readiness --no-interaction',
        ], $runtimeCheck);
        self::assertContains('@runtime:check', $development);

        $preflight = array_search('@runtime:check', $development, true);
        $processes = array_search(
            'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1 --timeout=0" "php artisan pail --timeout=0" "npm run dev" --names=server,queue,logs,vite --kill-others',
            $development,
            true,
        );

        self::assertIsInt($preflight);
        self::assertIsInt($processes);
        self::assertLessThan($processes, $preflight);
    }

    public function test_key_ensure_command_is_registered_without_resolving_runtime_auth_services(): void
    {
        $bootstrap = (string) file_get_contents($this->root.'/bootstrap/app.php');
        $command = (string) file_get_contents($this->root.'/app/Console/Commands/EnsureApplicationKeyCommand.php');

        self::assertStringContainsString('EnsureApplicationKeyCommand::class', $bootstrap);
        self::assertStringNotContainsString('__construct(', $command);
        self::assertStringContainsString('Encrypter::supported(', $command);
    }

    /** @return array<string,mixed> */
    private function composerConfiguration(): array
    {
        $contents = file_get_contents($this->root.'/composer.json');
        self::assertIsString($contents);

        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($ecoded);

        return $decoded;
    }
}
