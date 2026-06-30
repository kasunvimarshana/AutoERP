<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Str;
use Tests\TestCase;

final class EnsureApplicationKeyCommandTest extends TestCase
{
    private string $originalEnvironmentFile;
    private mixed $originalApplicationKey;
    private string $temporaryEnvironmentFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvironmentFile = $this->app->environmentFile();
        $this->originalApplicationKey = config('app.key');
        $this->temporaryEnvironmentFile = '.env.key-test-'.Str::uuid();
        $this->app->loadEnvironmentFrom($this->temporaryEnvironmentFile);
    }

    protected function tearDown(): void
    {
        @unlink(base_path($this->temporaryEnvironmentFile));
        $this->app->loadEnvironmentFrom($this->originalEnvironmentFile);
        config()->set('app.key', $this->originalApplicationKey);

        parent::tearDown();
    }

    public function test_it_generates_a_key_only_when_no_key_source_exists(): void
    {
        config()->set('app.key', '');
        $this->writeEnvironment("APP_NAME=AutoERP\nAPP_KEY=\nAPP_DEBUG=true\n");

        $this->artisan('app:key:ensure')
            ->expectsOutput('APP_KEY was generated because no key source was configured.')
            ->assertExitCode(0);

        $key = $this->environmentValue('APP_KEY');
        self::assertNotNull($key);
        self::assertStringStartsWith('base64:', $key);
        self::assertSame(32, strlen((string) base64_decode(substr($key, 7), true)));
    }

    public function test_it_preserves_an_existing_valid_environment_file_key(): void
    {
        $key = 'base64:'.base64_encode(str_repeat('k', 32));
        config()->set('app.key', $key);
        $contents = "APP_NAME=AutoERP\nAPP_KEY={$key}\n";
        $this->writeEnvironment($contents);

        $this->artisan('app:key:ensure')
            ->expectsOutput('APP_KEY already exists in the environment file and is valid. No change was made.')
            ->assertExitCode(0);

        self::assertSame($contents, $this->readEnvironment());
    }

    public function test_it_preserves_a_valid_runtime_key_when_the_environment_file_is_blank(): void
    {
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('r', 32)));
        $contents = "APP_NAME=AutoERP\nAPP_KEY=\n";
        $this->writeEnvironment($contents);

        $this->artisan('app:key:ensure')
            ->expectsOutput('APP_KEY is supplied by the runtime environment. The environment file was not changed.')
            ->assertExitCode(0);

        self::assertSame($contents, $this->readEnvironment());
    }

    public function test_it_rejects_an_invalid_existing_key_without_overwriting_it(): void
    {
        config()->set('app.key', '');
        $contents = "APP_NAME=AutoERP\nAPP_KEY=invalid-key\n";
        $this->writeEnvironment($contents);

        $this->artisan('app:key:ensure')
            ->expectsOutput('APP_KEY exists in the environment file but is invalid. Correct it explicitly; the existing value was not overwritten.')
            ->assertExitCode(1);

        self::assertSame($contents, $this->readEnvironment());
    }

    public function test_it_rejects_a_runtime_and_environment_file_key_mismatch(): void
    {
        $fileKey = 'base64:'.base64_encode(str_repeat('f', 32));
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('r', 32)));
        $contents = "APP_KEY={$fileKey}\n";
        $this->writeEnvironment($contents);

        $this->artisan('app:key:ensure')
            ->expectsOutput('Effective APP_KEY does not match the environment file. Clear cached configuration and remove conflicting process-level APP_KEY values.')
            ->assertExitCode(1);

        self::assertSame($contents, $this->readEnvironment());
    }

    public function test_it_appends_the_key_when_the_environment_file_has_no_key_entry(): void
    {
        config()->set('app.key', '');
        $this->writeEnvironment("APP_NAME=AutoERP\nAPP_DEBUG=true\n");

        $this->artisan('app:key:ensure')->assertExitCode(0);

        self::assertNotNull($this->environmentValue('APP_KEY'));
        self::assertStringContainsString("APP_DEBUG=true\nAPP_KEY=base64:", $this->readEnvironment());
    }

    public function test_it_rejects_multiple_application_key_entries(): void
    {
        config()->set('app.key', '');
        $contents = "APP_KEY=base64:".base64_encode(str_repeat('a', 32))."\n"
            ."APP_KEY=base64:".base64_encode(str_repeat('b', 32))."\n";
        $this->writeEnvironment($contents);

        $this->artisan('app:key:ensure')
            ->expectsOutput('The environment file contains multiple APP_KEY entries. Keep one explicit source of truth.')
            ->assertExitCode(1);

        self::assertSame($contents, $this->readEnvironment());
    }

    private function writeEnvironment(string $contents): void
    {
        file_put_contents(base_path($this->temporaryEnvironmentFile), $contents);
    }

    private function readEnvironment(): string
    {
        return (string) file_get_contents(base_path($this->temporaryEnvironmentFile));
    }

    private function environmentValue(string $name): ?string
    {
        if (preg_match('/^'.preg_quote($name, '/').'=(.*)$/m', $this->readEnvironment(), $matches) !== 1) {
            return null;
        }

        return trim((string) $matches[1]);
    }
}
