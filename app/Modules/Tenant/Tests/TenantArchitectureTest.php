<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Tenant\Services\Subscriptions\TenantSubscriptionLifecycleService;
use Modules\Tenant\Services\TenantActorSnapshotFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class TenantArchitectureTest extends TestCase
{
    public function test_tenant_owned_models_use_the_tenant_scope_foundation(): void
    {
        $modelDirectory = dirname(__DIR__).'/Models';
        $allowedControlPlaneModels = [
            'TenantModel.php',
            'TenantPlanModel.php',
            'TenantPlanRevisionModel.php',
        ];

        foreach (glob($modelDirectory.'/*.php') ?: [] as $file) {
            if (in_array(basename($file), $allowedControlPlaneModels, true)) {
                continue;
            }

            $source = (string) file_get_contents($file);
            self::assertStringContainsString(
                'extends TenantOwnedModel',
                $source,
                basename($file).' must use the tenant-owned model boundary.',
            );
        }
    }

    public function test_all_direct_tenant_foreign_keys_restrict_hard_deletion(): void
    {
        foreach ($this->migrationFiles() as $file) {
            $source = (string) file_get_contents($file->getPathname());
            foreach (explode(';', $source) as $statement) {
                $referencesTenant = str_contains($statement, "constrained('tenants'")
                    || str_contains($statement, "on('tenants')");
                if (! $referencesTenant) {
                    continue;
                }

                self::assertStringNotContainsString(
                    'cascadeOnDelete()',
                    $statement,
                    $file->getPathname().' must not cascade-delete tenant-owned data.',
                );
                self::assertStringContainsString(
                    'restrictOnDelete()',
                    $statement,
                    $file->getPathname().' must restrict hard tenant deletion.',
                );
            }
        }
    }

    public function test_every_tenant_aware_job_restores_context_through_queue_middleware(): void
    {
        foreach ($this->phpFiles(dirname(__DIR__, 2)) as $file) {
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/implements[^\{]+\bTenantAwareJobInterface\b/s', $source) !== 1) {
                continue;
            }
            if (! str_contains($source, 'final class') || str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            self::assertStringContainsString('function middleware()', $source, $file->getPathname());
            self::assertStringContainsString('RestoreTenantJobContext', $source, $file->getPathname());
        }
    }

    public function test_subscription_lifecycle_actor_snapshot_dependency_resolves_to_tenant_owner_service(): void
    {
        $constructor = (new ReflectionClass(TenantSubscriptionLifecycleService::class))->getConstructor();

        self::assertNotNull($constructor);

        $parameter = null;
        foreach ($constructor->getParameters() as $candidate) {
            if ($candidate->getName() === 'actorSnapshots') {
                $parameter = $candidate;
                break;
            }
        }

        self::assertNotNull($parameter);
        self::assertInstanceOf(ReflectionNamedType::class, $parameter->getType());
        self::assertSame(TenantActorSnapshotFactory::class, $parameter->getType()->getName());
        self::assertTrue(class_exists(TenantActorSnapshotFactory::class));
    }

    /** @return list<SplFileInfo> */
    private function migrationFiles(): array
    {
        $files = [];
        foreach ($this->phpFiles(dirname(__DIR__, 2)) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'Migrations'.DIRECTORY_SEPARATOR)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
