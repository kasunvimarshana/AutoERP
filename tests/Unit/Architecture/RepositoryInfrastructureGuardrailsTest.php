<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class RepositoryInfrastructureGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_shared_eloquent_repository_centralizes_tenant_scoped_query_creation(): void
    {
        $path = $this->repoRoot.'/app/Modules/Core/Infrastructure/Persistence/Repositories/EloquentRepository.php';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('protected function newScopedQuery(): Builder', $source);
        $this->assertStringContainsString('protected function resolveCurrentTenantId(): ?int', $source);
        $this->assertStringContainsString('protected function modelHasTenantColumn(): bool', $source);
        $this->assertStringContainsString("\$query->where(\$this->model->getTable().'.tenant_id', \$tenantId);", $source);
        $this->assertStringContainsString("Schema::connection(\$connection)->hasColumn(\$table, 'tenant_id')", $source);
    }

    public function test_repository_implementations_do_not_depend_on_auth_or_request_facades(): void
    {
        $repositoriesRoot = $this->repoRoot.'/app/Modules';
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($repositoriesRoot, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $fileInfo->getPathname());
            if (! str_contains($path, '/Infrastructure/Persistence/Eloquent/Repositories/')) {
                continue;
            }

            $source = (string) file_get_contents($fileInfo->getPathname());

            foreach ([
                'use Illuminate\\Support\\Facades\\Auth;',
                'use Illuminate\\Support\\Facades\\Request;',
                'Auth::',
                'Request::',
            ] as $forbiddenSnippet) {
                if (str_contains($source, $forbiddenSnippet)) {
                    $violations[] = $path.' -> '.$forbiddenSnippet;
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Repository implementations must not depend on Auth/Request facades. Use tenant context resolved upstream.\n".implode("\n", $violations)
        );
    }

    public function test_user_device_repository_uses_shared_tenant_context_resolver(): void
    {
        $path = $this->repoRoot.'/app/Modules/User/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserDeviceRepository.php';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('$this->resolveCurrentTenantId()', $source);
        $this->assertStringNotContainsString('private function resolveTenantId()', $source);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\Auth;', $source);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\Request;', $source);
    }
}
