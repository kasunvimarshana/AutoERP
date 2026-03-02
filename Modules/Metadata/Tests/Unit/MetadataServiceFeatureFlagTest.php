<?php

declare(strict_types=1);

namespace Modules\Metadata\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Metadata\Application\Services\MetadataService;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;
use Modules\Metadata\Domain\Entities\FeatureFlag;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MetadataService feature flag management methods.
 *
 * Verifies method existence, visibility, parameter signatures, return types,
 * and delegation contracts for the full feature flag CRUD + toggle surface.
 * Pure-PHP — no database or Laravel bootstrap required.
 */
class MetadataServiceFeatureFlagTest extends TestCase
{
    private function makeService(
        ?CustomFieldRepositoryContract $fieldRepo = null,
        ?FeatureFlagRepositoryContract $flagRepo = null,
    ): MetadataService {
        return new MetadataService(
            $fieldRepo ?? $this->createMock(CustomFieldRepositoryContract::class),
            $flagRepo ?? $this->createMock(FeatureFlagRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // listFlags — method existence, visibility, delegation
    // -------------------------------------------------------------------------

    public function test_list_flags_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'listFlags'),
            'MetadataService must expose a public listFlags() method.'
        );
    }

    public function test_list_flags_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'listFlags');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_flags_accepts_no_parameters(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'listFlags');

        $this->assertCount(0, $reflection->getParameters());
    }

    public function test_list_flags_delegates_to_flag_repository_all(): void
    {
        $expected = new Collection();

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService(null, $flagRepo)->listFlags();

        $this->assertSame($expected, $result);
    }

    public function test_list_flags_returns_collection(): void
    {
        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->method('all')->willReturn(new Collection());

        $result = $this->makeService(null, $flagRepo)->listFlags();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // createFlag — method existence and signature
    // -------------------------------------------------------------------------

    public function test_create_flag_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'createFlag'),
            'MetadataService must expose a public createFlag() method.'
        );
    }

    public function test_create_flag_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'createFlag');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_flag_accepts_array_data_param(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'createFlag');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('array', (string) $params[0]->getType());
    }

    public function test_create_flag_return_type_is_feature_flag(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'createFlag');

        $this->assertStringContainsString('FeatureFlag', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // updateFlag — method existence and signature
    // -------------------------------------------------------------------------

    public function test_update_flag_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'updateFlag'),
            'MetadataService must expose a public updateFlag() method.'
        );
    }

    public function test_update_flag_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'updateFlag');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_flag_accepts_id_and_data_params(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'updateFlag');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    // -------------------------------------------------------------------------
    // deleteFlag — method existence, signature, return type
    // -------------------------------------------------------------------------

    public function test_delete_flag_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'deleteFlag'),
            'MetadataService must expose a public deleteFlag() method.'
        );
    }

    public function test_delete_flag_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteFlag');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_flag_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteFlag');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }

    public function test_delete_flag_accepts_id_param(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteFlag');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // toggleFlag — method existence and signature
    // -------------------------------------------------------------------------

    public function test_toggle_flag_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'toggleFlag'),
            'MetadataService must expose a public toggleFlag() method.'
        );
    }

    public function test_toggle_flag_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'toggleFlag');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_toggle_flag_accepts_single_id_param(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'toggleFlag');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_toggle_flag_return_type_is_feature_flag(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'toggleFlag');

        $this->assertStringContainsString('FeatureFlag', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // isFeatureEnabled — existing method still present (regression guard)
    // -------------------------------------------------------------------------

    public function test_is_feature_enabled_method_still_exists(): void
    {
        $this->assertTrue(
            method_exists(MetadataService::class, 'isFeatureEnabled'),
            'MetadataService::isFeatureEnabled() must still be present after adding new flag methods.'
        );
    }

    public function test_is_feature_enabled_delegates_to_flag_repository(): void
    {
        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->expects($this->once())
            ->method('isFlagEnabled')
            ->with('pharma_compliance')
            ->willReturn(true);

        $result = $this->makeService(null, $flagRepo)->isFeatureEnabled('pharma_compliance');

        $this->assertTrue($result);
    }
}
