<?php

declare(strict_types=1);

namespace Modules\Metadata\Tests\Unit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Metadata\Application\DTOs\CreateCustomFieldDTO;
use Modules\Metadata\Application\Services\MetadataService;
use Modules\Metadata\Domain\Contracts\CustomFieldRepositoryContract;
use Modules\Metadata\Domain\Contracts\FeatureFlagRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MetadataService.
 *
 * Read methods (listFields, paginateFields, showField, isFeatureEnabled) are
 * tested via mocked repositories — no database or Laravel bootstrap required.
 *
 * Write methods (createField, updateField, deleteField) use DB::transaction()
 * which requires the Laravel facade; those paths are covered by feature tests.
 */
class MetadataServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listFields — delegates to findByEntityType
    // -------------------------------------------------------------------------

    public function test_list_fields_delegates_to_find_by_entity_type(): void
    {
        $collection = new Collection();

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findByEntityType')
            ->with('product')
            ->willReturn($collection);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->listFields('product');

        $this->assertSame($collection, $result);
    }

    public function test_list_fields_passes_entity_type_string_correctly(): void
    {
        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findByEntityType')
            ->with('sales_order')
            ->willReturn(new Collection());

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $service->listFields('sales_order');
    }

    public function test_list_fields_returns_collection(): void
    {
        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->method('findByEntityType')->willReturn(new Collection());

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->listFields('customer');

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // paginateFields — delegates to repository paginate
    // -------------------------------------------------------------------------

    public function test_paginate_fields_delegates_with_default_per_page(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('paginate')
            ->with(15)
            ->willReturn($paginator);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->paginateFields();

        $this->assertSame($paginator, $result);
    }

    public function test_paginate_fields_passes_custom_per_page(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('paginate')
            ->with(50)
            ->willReturn($paginator);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $service->paginateFields(50);
    }

    // -------------------------------------------------------------------------
    // showField — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_field_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findOrFail')
            ->with(7)
            ->willReturn($model);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->showField(7);

        $this->assertSame($model, $result);
    }

    public function test_show_field_accepts_string_uuid(): void
    {
        $model = $this->createMock(Model::class);

        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);
        $fieldRepo->expects($this->once())
            ->method('findOrFail')
            ->with('uuid-abc-123')
            ->willReturn($model);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $service->showField('uuid-abc-123');
    }

    // -------------------------------------------------------------------------
    // isFeatureEnabled — delegates to flagRepository
    // -------------------------------------------------------------------------

    public function test_is_feature_enabled_returns_true_when_flag_is_on(): void
    {
        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->expects($this->once())
            ->method('isFlagEnabled')
            ->with('pharma_compliance_mode')
            ->willReturn(true);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->isFeatureEnabled('pharma_compliance_mode');

        $this->assertTrue($result);
    }

    public function test_is_feature_enabled_returns_false_when_flag_is_off(): void
    {
        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->expects($this->once())
            ->method('isFlagEnabled')
            ->with('advanced_reporting')
            ->willReturn(false);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $result  = $service->isFeatureEnabled('advanced_reporting');

        $this->assertFalse($result);
    }

    public function test_is_feature_enabled_passes_flag_key_to_repository(): void
    {
        $fieldRepo = $this->createMock(CustomFieldRepositoryContract::class);

        $flagRepo = $this->createMock(FeatureFlagRepositoryContract::class);
        $flagRepo->expects($this->once())
            ->method('isFlagEnabled')
            ->with('custom_workflows')
            ->willReturn(false);

        $service = new MetadataService($fieldRepo, $flagRepo);
        $service->isFeatureEnabled('custom_workflows');
    }

    // -------------------------------------------------------------------------
    // CreateCustomFieldDTO — field mapping contract validation
    // -------------------------------------------------------------------------

    public function test_dto_field_type_is_preserved(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'product',
            'field_name'  => 'expiry_date',
            'field_label' => 'Expiry Date',
            'field_type'  => 'date',
        ]);

        $this->assertSame('date', $dto->fieldType);
    }

    public function test_dto_entity_type_is_preserved(): void
    {
        $dto = CreateCustomFieldDTO::fromArray([
            'entity_type' => 'purchase_order',
            'field_name'  => 'cost_centre',
            'field_label' => 'Cost Centre',
            'field_type'  => 'text',
        ]);

        $this->assertSame('purchase_order', $dto->entityType);
    }
}
