<?php

declare(strict_types=1);

namespace Modules\Metadata\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Metadata\Application\Services\MetadataService;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Expanded structural compliance tests for MetadataService write-path methods.
 *
 * Covers showField, updateField, deleteField, paginateFields signatures,
 * service instantiation, and repository delegation contracts.
 */
class MetadataServiceWritePathExpandedTest extends TestCase
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
    // Service instantiation — structural smoke test
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_mocked_contracts(): void
    {
        $service = $this->makeService();

        $this->assertInstanceOf(MetadataService::class, $service);
    }

    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_show_field_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'showField');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_field_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'updateField');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_field_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteField');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_paginate_fields_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'paginateFields');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_show_field_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'showField');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertNotNull($params[0]->getType());
    }

    public function test_update_field_accepts_id_and_data_params(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'updateField');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_delete_field_accepts_single_id_param(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'deleteField');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_paginate_fields_has_default_per_page_of_15(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'paginateFields');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('perPage', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertSame(15, $params[0]->getDefaultValue());
    }

    // -------------------------------------------------------------------------
    // showField — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_field_delegates_to_repository_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($model);

        $result = $this->makeService($fieldRepo)->showField(42);

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // listFields — delegates to repository findByEntityType
    // -------------------------------------------------------------------------

    public function test_list_fields_delegates_to_find_by_entity_type(): void
    {
        $collection = new Collection();

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findByEntityType')
            ->with('product')
            ->willReturn($collection);

        $result = $this->makeService($fieldRepo)->listFields('product');

        $this->assertSame($collection, $result);
    }

    public function test_list_fields_accepts_entity_type_string(): void
    {
        $reflection = new \ReflectionMethod(MetadataService::class, 'listFields');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('entityType', $params[0]->getName());
    }
}
